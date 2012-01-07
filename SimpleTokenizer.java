import edu.stanford.nlp.process.*;
import edu.stanford.nlp.ling.Word;
import java.io.*;
import java.util.*;

public class SimpleTokenizer
{
	public static void main(String[] args) {
		InputStreamReader reader = new InputStreamReader(System.in);

		WordTokenFactory wtf = new WordTokenFactory();

		PTBTokenizer<Word> tokenizer = new PTBTokenizer<Word>(reader, wtf, "tokenizeNLs=true");
		
		List<Word> words = tokenizer.tokenize();
		for(Word w : words) {
			if(w.word().equals("*NL*"))
				System.out.println();
			else
				System.out.print(w.word() + " ");
		}
	}
}
