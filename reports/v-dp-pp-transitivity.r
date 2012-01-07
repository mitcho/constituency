library('calibrate')
library('randtoolbox')

selectnames <- function(X, crit) {
  mynames = row.names(X)
  mynames[!crit] = ""
  mynames
}

data <- read.delim("~/hyperlink-constituency/reports/v-dp-pp-transitivity.out")
attach(data)
plot(link.on.VO, link.on.VP)

#textxy(link.on.VO, link.on.VP, selectnames(data, link.on.VP - link.on.VO >= 4 | link.on.VP - link.on.VO <= -3))