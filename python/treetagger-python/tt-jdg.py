# -*- coding: utf-8 -*-

import sys
from treetagger3 import TreeTagger

tt = TreeTagger(encoding='utf-8',language='french')
with open (sys.argv[1], "r") as myfile:
    data=myfile.read()
tags = tt.tag(data)
tagstats = {}
for tag in tags:
	wordCategory = tag[1]
	tagstats[wordCategory] = tagstats.get(wordCategory, 0) + 1

for tag in tagstats:
	print tag+"\t"+str(tagstats[tag])