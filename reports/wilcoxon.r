# Read in lengths
lengths = read.table("lengths.out",header=TRUE,sep="\t")

# Name vectors of actual and expected number of instances of 
# link- and sentence-length combinations
actual <- lengths[[3]]
expected <- lengths[[4]]

# Do Wilcoxon test
wilcox.test(actual, expected, paired=TRUE)
