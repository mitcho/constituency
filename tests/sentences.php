<?php

// This file tests the sentence-breaker, MXTERMINATOR.

// Non-newline whitespace is annoying with standard string comparison, so we ignore it.
function strwcmp($string1, $string2) {
	$string1 = trim(str_replace(array(" ", "\t", "\r"), "", $string1));
	$string2 = trim(str_replace(array(" ", "\t", "\r"), "", $string2));

	return $string1 == $string2;
}

// Entry 23363

$text1 = <<<TEXT
Is the U.S. suffocating reform in Iran? "'Despite sporadic verbal concern with the condition of human rights in Iran, the U.S. is protecting and providing clandestine support to the right-wing conservatives in Iran,' says Sayed Ali Asghar Gharavi, a member of the banned but tolerated Iran Freedom Movement (IFM), the country's leading opposition party. 'The U.S. government in no way favors the coming to power of the reformist groups in Iran and is secretly supporting the religious conservatives.' Government insiders in Iran allege that the deal, first proffered by British Foreign Secretary Jack Straw, is simple: If the hard-liners quietly support the United States in Iraq, Washington will quietly support them. U.S. State Department officials declined to comment." It seems unlikely that the Bush administration would side with the mullahs, but considering the U.S.'s troubled history with Iranian democracy, it's not inconceivable. Perhaps this is why Michael Ledeen's cries of alarm aren't being heeded.
TEXT;

$split1 = <<<TEXT
Is the U.S. suffocating reform in Iran?
"'Despite sporadic verbal concern with the condition of human rights in Iran, the U.S. is protecting and providing clandestine support to the right-wing conservatives in Iran,' says Sayed Ali Asghar Gharavi, a member of the banned but tolerated Iran Freedom Movement (IFM), the country's leading opposition party.
'The U.S. government in no way favors the coming to power of the reformist groups in Iran and is secretly supporting the religious conservatives.'
Government insiders in Iran allege that the deal, first proffered by British Foreign Secretary Jack Straw, is simple: If the hard-liners quietly support the United States in Iraq, Washington will quietly support them.
U.S. State Department officials declined to comment."
It seems unlikely that the Bush administration would side with the mullahs, but considering the U.S.'s troubled history with Iranian democracy, it's not inconceivable.
Perhaps this is why Michael Ledeen's cries of alarm aren't being heeded.
TEXT;

$result1 = splitSentences($text1);
test_assert(strwcmp($result1, $split1), "23363 split incorrectly.");

// Entry 17060

$text2 = <<<TEXT
Spider-Man artist makes amazing effort to save a child’s life. Amazing Spider-Man artist John Romita Jr. is spending the next 36 hours continuously sketching Spider-Man in New York to raise money to help pay for his two-year-old niece’s battle with cancer. I’m pleased to see a comic book artist striving to be a role-model and a hero. If any MeFi members in NYC are going to check this out, I'd be interested in reading your comments.
TEXT;

$split2 = <<<TEXT
Spider-Man artist makes amazing effort to save a child's life.
Amazing Spider-Man artist John Romita Jr. is spending the next 36 hours continuously sketching Spider-Man in New York to raise money to help pay for his two-year-old niece's battle with cancer.
I'm pleased to see a comic book artist striving to be a role-model and a hero.
If any MeFi members in NYC are going to check this out, I'd be interested in reading your comments.
TEXT;

$result2 = splitSentences($text2);
test_assert(strwcmp($result2, $split2), "17060 split incorrectly.");

// This test fails at the moment. Maybe we'll look at sentence-splitter code or find a better one later.
/*
$text3 = "I want to get in to M.I.T. And when I do, I will be awesome.";
$split3 = "I want to get in to M.I.T. \nAnd when I do, I will be awesome.";
$result3 = splitSentences($text3);
test_assert(strwcmp($result3, $split3), "Split after M.I.T.");
*/

$text4 = "I spoke to M.I.T. professor Noam Chomsky.";
$split4 = "I spoke to M.I.T. professor Noam Chomsky.";
test_assert(trim(splitSentences($text4)) == $split4, "Don't split after M.I.T.");

$text5 = "Hazard types: snow... and more snow.";
$split5 = "Hazard types: snow... and more snow.";
test_assert(trim(splitSentences($text5)) == $split5, "Don't necessarily split after ellipsis.");
?>
