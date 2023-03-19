# MFTF-Buddy tests
Magento functional tests made easy!

---

## Steps to run MFTF tests in MFTF-Buddy cloud:

1. Register at https://www.mftf-buddy.com to get `MFTF Buddy Secret Key`
2. Install this module in your Magento project: 
  - `composer require mftf-buddy/tests`
  - `bin/magento module:enable MFTFBuddy_Tests`
  - `bin/magento setup:upgrade`
3. Set `MFTF Buddy Secret Key` as MB_SECRET_KEY env variable in dev/tests/acceptance/.env_mb file
4. Run suite of tests via Magento CLI command:<br />
  `bin/magento mftf-buddy:run-tests <suite-name>`<br />
   This command will output Test Session ID, which can be used to get detailed logs from finished test run
5. Get detailed logs from finished test run:<br />
  `bin/magento mftf-buddy:dump-logs <test-session-id> [<output-dir>]`
