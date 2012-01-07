library('calibrate')

selectnames <- function(X, crit) {
  mynames = row.names(X)
  mynames[!crit] = ""
  mynames
}


data <- read.delim("~/hyperlink-constituency/reports/v-pp-transitivity.out")

datalimited <- data[(data$pp.transitivity > 0 | data$intransitivity > 0) & data$link.on.verb > 1 & data$link.on.VP > 1,]
attach(datalimited)

datalimited$VP.preference <- (link.on.VP - link.on.verb)/(link.on.VP + link.on.verb)
datalimited$relative.transitivity <- (pp.transitivity - intransitivity)/(pp.transitivity + intransitivity)
attach(datalimited)

plot(relative.transitivity, VP.preference)
abline(lm('VP.preference ~ relative.transitivity'))
textxy(relative.transitivity, VP.preference, selectnames(datalimited, TRUE))
