#!/usr/bin/env sh

COMPILER="../kuteforth.php"
COMPILER_FLAGS="-sr"

testfolder=$(dirname ${0})
backfolder=$(pwd)

FILE=$(realpath $1)

cd ${testfolder}

if [ $# -ne 1 ]; then
	printf "Require a file to generate output of\n"
	cd ${backfolder}
	exit 1
fi
if [ -f ${FILE} ]; then
	basename=$(echo ${FILE} | cut -f 1 -d '.')
	printf "Generating output for test [%s]\n" ${basename}
	${COMPILER} ${COMPILER_FLAGS} $FILE | tee "${basename}.txt"
	rm ${basename}
else
	printf "Require a path to a file that exists\n"
	cd ${backfolder}
	exit 1
fi

cd ${backfolder}
