#!/usr/bin/env sh
failed=0

COMPILER="../kuteforth.php"

testfolder=$(dirname ${0})
backfolder=$(pwd)

cd ${testfolder}

for element in *.kf; do
	printf "[TESTS]: running test ${element}\n"
	${COMPILER} -sr ${element} | tee output.txt
	basename=$(echo ${element} | cut -f 1 -d '.')
	cmp -s output.txt ${basename}.txt
	if [ ${?} -ne 0 ]; then
		printf "[TESTS]: Test ${basename} has failed\n"
		failed=$( echo ${failed}+1 | bc)
	fi
	rm output.txt ${basename}
done

cd ${backfolder}

if [ ${failed} -ne 0 ]; then
	printf "[TESTS]: [${failed}] tests have failed\n"
	exit 1
else
	printf "[TESTS]: All tests ran succesfully\n"
fi
