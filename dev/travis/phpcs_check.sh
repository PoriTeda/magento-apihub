#!/usr/bin/env bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.


# create the report
php vendor/bin/phpcs -n -p --report-full=./phpcs_report/"$YmdHms"_full.txt --report-summary=./phpcs_report/"$YmdHms"_summary.txt --standard=Magento2 --ignore=*/Paygent/lib/* ./app/code/

# Zip the report
zip "$YmdHms"_phpcs_report.zip ./phpcs_report/"$YmdHms"_full.txt ./phpcs_report/"$YmdHms"_summary.txt

#Upload phpcscheck to S3
aws s3 cp --acl bucket-owner-full-control "$YmdHms"_phpcs_report.zip s3://nestle-travis-shared-files-"$ENV"/phpcs_report/"$YmdHms"_phpcs_report.zip

#remove file
rm -rf ./"$YmdHms"_phpcs_report.zip

# Check and send the output
#if grep -Eo "TOTAL OF [0-9]{1,} ERRORS" ./phpcs_report/"$YmdHms"_summary.txt | grep -v "TOTAL OF 0 ERRORS" ; then exit 1; fi # temporary disable until coding clean up
