# Changelog

## 1.0.0

### Nov 14, 2024

- remove NaadRSSClientTest
- tweak test output with `--testdox` option

### Oct 23, 2024

- Add github workflows for enforcing tests on pull requests:
  - PHPCS ([DESCW-2623](https://citz-gdx.atlassian.net/browse/DESCW-2623))
  - PHPUNIT ([DESCW-2658](https://citz-gdx.atlassian.net/browse/DESCW-2658))

### October 15, 2024

- Add support for `docker-compose` to stand up a local development environment: ([DESCW-2672](https://citz-gdx.atlassian.net/browse/DESCW-2672))
  - Includes phpMyAdmin to inspect the alerts in the new alerts table
  - Updates README with instructions on running the docker compose and phpMyAdmin.
  - Adds a `docker-compose.yml` file to the root of the repository.
  - Adds .vscode to the `.gitignore`.
  - Adds an .env file to feed environment config to the `docker-compose.yaml` config.

### March 11, 2024

- Add monolog as the logging channel for Naad socket client ([DESCW-1901](https://apps.itsm.gov.bc.ca/jira/browse/DESCW-1901))

### January 19, 2024

- Added function to determine when a socket response is a heartbeat or an alert ([DESCW-1870](https://apps.itsm.gov.bc.ca/jira/browse/DESCW-1870))
- Added multi-part message handling for when alert text is too long to be read in a single socket_read() ([DESCW-1869](https://apps.itsm.gov.bc.ca/jira/browse/DESCW-1869))

### January 10, 2024

- Created NaadRssClient class to fetch and parse NAAD RSS feed ([DESCW-1777](https://apps.itsm.gov.bc.ca/jira/browse/DESCW-1777))

### January 2, 2024

- OpenShift Deployment ([DESCW-1868](https://apps.itsm.gov.bc.ca/jira/browse/DESCW-1868))
- Created NaadConnector class to connect to NAAD socket ([DESCW-1778](https://apps.itsm.gov.bc.ca/jira/browse/DESCW-1778))
- Added Docker build configuration
- Added OpenShift BuildConfig and ImageStream
- Added phpcs, fixed code style issues
- Added phpunit
