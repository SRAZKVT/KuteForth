failed=0

testfolder=$(dirname ${0})
backfolder=$(pwd)

cd ${testfolder}

for element in *.kf; do
	../kuteforth.php -sr ${element} > output.txt
	basename=$(echo ${element} | cut -f 1 -d '.')
	cmp -s output.txt ${basename}.txt
	if [ ${?} -ne 0 ]; then
		printf "Test ${basename} has failed\n"
		failed=$( echo ${failed}+1 | bc)
	fi
	rm output.txt ${basename}
done

cd ${backfolder}

if [ ${failed} -ne 0 ]; then
	printf "[${failed}] tests have failed\n"
	exit 1
else
	printf "All tests ran succesfully\n"
fi
