#/bin/bash

echo "******************************************"
echo "Check directory"
echo "******************************************"
mkdir -pv ./tmp
if [ "$(ls -A ./tmp)" ];then
  echo "./tmp is not empty."
  echo "Program exits."
  exit 1
fi

echo "******************************************"
echo "Move unnecessary library files to ./tmp."
echo "******************************************"

CNT=0
LIBRARY_NAME=''
while read LINE
do
  case ${LINE} in
    \[*\])  LIBRARY_NAME=$(echo "${LINE}" | sed -e "s/^\[//" | sed -e "s/\]$//")
            mkdir  -p ./tmp/${LIBRARY_NAME}
            echo "*** ${LIBRARY_NAME} ***" 
            continue
            ;;

    *)      if [ -z "${LIBRARY_NAME}" ];then
              echo "ERROR: Invalid file format: \"./remove_file_list.txt\""
              exit 1
            fi
            ;;
  esac

  FILE_NAME=$(eval "stat -c %n ${LINE} 2> /dev/null")
  if [ ${?} -eq 1 ];then
    echo "WARNING: ${LINE} does not existed."
  else
    mv -v ${FILE_NAME} ./tmp/${LIBRARY_NAME}
    CNT=$(expr ${CNT} + 1)
  fi
done < "./remove_file_list.txt"

echo "******************************************"
echo "Result"
echo "******************************************"
echo "${CNT} files moved to ./tmp."
exit 0

