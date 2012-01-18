<?php

include('../functions.php');

$entry = <<<HTML
<a href="http://www.sjmercury.com/svtech/news/coverage/aff092299.htm">If you've seen Patrick Naughton's (former Infoseek Exec) FBI affidavit before,</a> you can see that when he was arrested, he turned over his laptop, admitting to having numerous kiddie porn images on it, and he knew the person he chatted with was a woman (since they spoke to each other on the phone several times). In the chats, he also stated on several occasions that he messed around with several other young girls before. So now that his trial is beginning in LA, and he's facing up to 40 years in prision, his lawyers are claiming that <a href='http://dailynews.yahoo.com/h/zd/19991207/tc/19991207034.html'>the chats were pure fantasy</a> and he never thought he'd actually meet a young girl. His lawyers are also claiming that the kiddie porn on his hard drive was <a href='http://dailynews.yahoo.com/h/zd/19991129/tc/19991129072.html'>unsolicited and he hadn't gotten around to deleting the unwanted images</a>. Yeah, riiiiiight. I hate to say it, but this guy is so far beyond a doubt guilty that his defense sounds like a last-gasp effort to avoid the inevitable. 
		<br>
HTML;

$link = 'the chats were pure fantasy';

$split = <<<HTML
<a href="http://www.sjmercury.com/svtech/news/coverage/aff092299.htm">If you've seen Patrick Naughton's (former Infoseek Exec) FBI affidavit before,</a> you can see that when he was arrested, he turned over his laptop, admitting to having numerous kiddie porn images on it, and he knew the person he chatted with was a woman (since they spoke to each other on the phone several times).
In the chats, he also stated on several occasions that he messed around with several other young girls before.
So now that his trial is beginning in LA, and he's facing up to 40 years in prision, his lawyers are claiming that <a href='http://dailynews.yahoo.com/h/zd/19991207/tc/19991207034.html'>the chats were pure fantasy</a> and he never thought he'd actually meet a young girl.
His lawyers are also claiming that the kiddie porn on his hard drive was <a href='http://dailynews.yahoo.com/h/zd/19991129/tc/19991129072.html'>unsolicited and he hadn't gotten around to deleting the unwanted images</a>.
Yeah, riiiiiight.
I hate to say it, but this guy is so far beyond a doubt guilty that his defense sounds like a last-gasp effort to avoid the inevitable. 
		<br>
HTML;

echo $entry;