<?php

include_once("connect_mysql.php");
class Pattern {
	var $query;
	var $regexp;
	var $error = false;
	var $backrefs;
	var $backrefCount = 0;
	var $return = array(); // only used by queryDb/push
	var $allowStrayLINKs = false;
	
	var $aliases = array(
		'N' => '(NN|NNS|NNP|NNPS)',
		'A' => '(JJ|JJR|JJS)',
		'D' => '(DT|CD|PDT)',
		'P' => 'IN',
		'V' => '(MD|VB|VBD|VBG|VBN|VBP|VBZ)',
		'ADV' => '(RB|RBR|RBS)'
	);
	
	// used by aliasing in buildRegexp
	function _makeNodeLabel( $nodeLabel ) {
		return "\\({$nodeLabel}[[:space:]]";
	}
	
	function Pattern($query, $allowStrayLINKs = false) {
		$this->query = $query;
		$this->allowStrayLINKs = $allowStrayLINKs;
		$this->backrefs = array();
		$this->buildRegexp();
		// validate the pattern
	}
	
	// Get a MySQL LIKE statement which matches *very* greedily.
	// It can only be used to limit the search space to things with the correct
	// terminals or non-terminals.
	function getLike() {
		// swap out node labels which are aliases:
		$pattern = '/\\((' . implode( '|', array_keys($this->aliases) ) . ')\s+/';
		$query = preg_replace( $pattern, '( ', $this->query );

		// split on non-words...
		$words = preg_split("!\W+!", $query);
		$words = array_filter($words, create_function('$x', 'return !empty($x) && $x != "NODE" && $x != "WORD";'));
		
		return "%" . join("%", $words) . "%";
	}

	// Get a MySQL REGEXP statement which matches relatively greedily.
	// This pattern is used to further narrow things down
	function buildRegexp() {
		// SPACE MANAGEMENT:
		// there are no spaces between terminals and )
		$regexp = preg_replace("!\\s+\\)!", ")", $this->query);
		// replace spaces.
		$regexp = preg_replace("!\\s+!", "[[:space:]]*", $regexp);

		// Allow for unspecified labels:
		$regexp = str_replace('([[:space:]]', '([[:alnum:]]+[[:space:]]', $regexp);	

		// Allow for unspecified labels:
		$regexp = str_replace('([[:space:]]', '([[:alnum:]]+[[:space:]]', $regexp);	
				
		// REGEX-IFY:
		// replace ( or ) with \( or \), respectively:

		$regexp = str_replace(')', '\\)>?', $regexp);
		$regexp = str_replace('(', '<?\\(', $regexp);
		$regexp = str_replace('$', '\\$', $regexp);

		// Node label aliases:
		$labels = array_map( array($this, '_makeNodeLabel'), array_keys($this->aliases) );
		$expanded = array_map( array($this, '_makeNodeLabel'), array_values($this->aliases) );

		$regexp = str_replace($labels, $expanded, $regexp);	

		// WILDCARD MANAGEMENT:
		$regexp = str_replace("<", "(<<<LINK[[:space:]]*)", $regexp);
		$regexp = str_replace(">", "([[:space:]]*LINK;)", $regexp);
		$regexp = preg_replace("!\\bWORD\\b!", "(?:[[:space:]]*[^()[:space:]]+)", $regexp);

		if ($this->allowStrayLINKs) {
			$noderegex =
				"(?:(?msx)" . # multiline, . matches newline, extended
					"(?'node'" . # start definition of a recursive subpattern
						"(<<<LINK|[[:space:]]*)*\\([A-Z.,$\\!]+[[:space:]]+" . # whitespace, literal open paren, node type, whitespace: '(NP '
							"(?:[^()]+|" . # either a bunch of text, or
							"(?:(?&node)([[:space:]]|LINK;)*)" . # a node followed by spaces: '(DT the)', '(JJ brown)', '(NN dog)'
						"+)" . # one or more of those.
					"[[:space:]]*\\)([[:space:]]|LINK;)*" .# more space, literal close paren, whitespace: ')'
				"))"; # end pattern
		} else {		
			$noderegex =
				"(?:(?msx)" . # multiline, . matches newline, extended
					"(?'node'" . # start definition of a recursive subpattern
						"[[:space:]]*\\([A-Z.,$\\!]+[[:space:]]+" . # whitespace, literal open paren, node type, whitespace: '(NP '
							"(?:[^()]+|" . # either a bunch of text, or
							"(?:(?&node)[[:space:]]*)" . # a node followed by spaces: '(DT the)', '(JJ brown)', '(NN dog)'
						"+)" . # one or more of those.
					"[[:space:]]*\\)[[:space:]]*" .# more space, literal close paren, whitespace: ')'
				"))"; # end pattern
		}

		$regexp = preg_replace("!\\bNODE\\b!", trim($noderegex), $regexp, 1);
		$regexp = preg_replace("!\\bNODE\\b!", "((?msx)(?&node))", $regexp);
		$regexp = preg_replace_callback("/{/", array($this, 'createBackref'), $regexp);
		$regexp = str_replace("}", ")", $regexp);
		return $this->regexp = $regexp;
	}
	
	function createBackref() {
		return "(?'backref" . ($this->backrefCount++) . "'";
	}
	
	function matchesBalance($parse, $id = -1) {
		// The things we need to do in this function are to:
		// 1. Make sure that the parentheses, as balanced based on the query, exist
		//    in the given match
		// 2. Make sure wildcards match their query's specification.
		$r = $this->regexp;
		if ((strpos($this->query, "<") === false) && (strpos($this->query, ">") === false)) {
			$parse = str_replace("<<<LINK", "", $parse);
			$parse = str_replace("LINK;", "", $parse);
		}

		$does_match = preg_match_all("!$r!msx", $parse, $matches);

		// HACK TO DEAL WITH IRREGULAR PLACMENT OF LINK;
		// TODO: fix the root of the problem! - mitcho
		while (!$does_match && preg_match("!LINK;\s*\\)!", $parse)) {
			$parse = preg_replace("!LINK;\\s*\\)!", ")LINK;", $parse);
			$does_match = preg_match_all("!$r!msx", $parse, $matches);
		}
		
		if ($does_match) {
			$backrefs = array();
			for ($i = 0; $i < $this->backrefCount; $i++) {
				$backrefs[$i] = $matches["backref{$i}"];
			}

			// Shoenfinkel!
			$shoenfinkeled_backrefs = array();
			for ($i = 0; $i < count($backrefs[0]); $i++) {
				$shoenfinkeled_backrefs[$i] = array();
				for ($j = 0; $j < $this->backrefCount; $j++) {
					$shoenfinkeled_backrefs[$i][$j] = $backrefs[$j][$i];
				}
			}
			
			if ($id > -1)
				$this->backrefs[$id] = $shoenfinkeled_backrefs;
			else
				$this->backrefs[] = $shoenfinkeled_backrefs;
		}
		return $does_match;
	}
	
	function queryDb($range = array(0, 1000)) {
		$this->queryDbCallback( array($this, 'push'), $range );
		return $this->return;
	}

	function push( $row ) {
		array_push($this->return, $row);
	}

	// range should be a range Array, but if it's an int, it should be -1, which means "everything"
	function queryDbCallback($callback, $range = array(0, 1000)) {
		// note that this will not work if your connect_mysql.php doesn't declare $dbh !
		// the syntax is $dbh = new PDO('mysql:host=hostname;dbname=databasename', username, password);
		global $dbh;

		// range set to -1 means no limit.
		if ( is_int($range) ) {
			if ( $range == -1 )
				$range = array(0, 100000000); // i.e. everything
			else
				return false;
		}

		$this->prep = $dbh->prepare("select id, stanford, content from " . ENTRIES_TABLE . 
				      " where stanford like :like and id >= {$range[0]} and id <= {$range[1]}");
		$this->prep->bindValue(':like', $this->getLike());

		$return = array();
		
		if ($this->prep->execute()) {
		  while ($row = $this->prep->fetch()) {
			  if ($this->matchesBalance($row['stanford'], $row['id']))
					call_user_func($callback, $row);
		  }
		}
		else {
		  echo "<div class='error'>";
		  print_r($this->prep->errorInfo());
		  echo "</div>";
		  return;
		}
		return $return;
	}

}
?>