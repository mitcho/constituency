STANFORD DEPENDENCIES.  Stanford Parser v1.6.3

There is now a manual for the English version of the Stanford
Dependencies representation:

    dependencies_manual.pdf

It should be consulted for the current set of dependency representations
and the correct commands for generating Stanford Dependencies together
with either the Stanford Parser or another parser.

A typed dependencies representation is also available for Chinese.  For
the moment the documentation consists of the code, and a brief
presentation in this paper:

Pi-Chuan Chang, Huihsin Tseng, Dan Jurafsky, and Christopher
D. Manning. 2009.  Discriminative Reordering with Chinese Grammatical
Relations Features.  Third Workshop on Syntax and Structure in Statistical
Translation.


--------------------------------------
0. Original dependencies scheme
--------------------------------------

For an overview of the original typed dependencies scheme, please look
at:

  Marie-Catherine de Marneffe, Bill MacCartney, and Christopher D.
  Manning. 2006. Generating Typed Dependency Parses from Phrase
  Structure Parses. 5th International Conference on Language Resources
  and Evaluation (LREC 2006).
  http://nlp.stanford.edu/~manning/papers/LREC_2.pdf


--------------------------------------
CHANGES IN ENGLISH TYPED DEPENDENCIES CODE -- JUNE 2010

No new dependency relations have been introduced.

There have been some significant improvements in the generated
dependencies, principally covering:
 - Better resolution of nsubj and dobj long distance dependencies
 - Better handling of conjunction distribution in CCprocessed option
 - Correction of bug in v1.6.2 that made certain verb dependents noun
   dependents.

