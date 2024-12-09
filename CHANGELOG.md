# Changelog

## 1.0.0

### DEC 09, 2024

- [DESCW-2741](https://citz-gdx.atlassian.net/browse/DESCW-2741)
- Dockerfile changed to use /app directory instead of /var/www/html (best practices)
- Dockerfile cleanup
- Readme updated to reflect changes to how we build this app for development

### DEC 06, 2024

- Update alerts table using request results ([DESCW-2765](https://citz-gdx.atlassian.net/browse/DESCW-2765))

### DEC 5, 2024

- [DESCW-2792](https://citz-gdx.atlassian.net/browse/DESCW-2786)
- add the ability to generate documentation using phpDocumentor
- add script in composer.json to generate documentation
- add script in composer.json to view documentation
- update readme with instructions for the above

### DEC 4, 2024

- [DESCW-2792](https://citz-gdx.atlassian.net/browse/DESCW-2792)
  - add .dockerignore
  - cleanup Dockerfile

### DEC 3, 2024

- [DESCW-2793](https://citz-gdx.atlassian.net/browse/DESCW-2793)
  - add custom config to phpcs.xml
  - improve phpcs linting to include the /tests folder
  - fix linter errors on the NaadSocketClientTest
- [DESCW-2800](https://citz-gdx.atlassian.net/browse/DESCW-2800)
  - use Guzzle instead of CURL for NaadRepositoryClient
  - Refactor NaadSocketClient to fix failing tests
  - fix null bug in Repository Client

### Nov 27, 2024

- ([DESCW-2739](https://citz-gdx.atlassian.net/browse/DESCW-2739))
- moved try/catch block directly to the possible failure point in the socket client module
- restored kustomization base for the app to previous import order
- test and update K8s deployment so it works properly with NaadVars class getter.
- fix typo in socket client NaadVars.
- Update Readme layout and instructions.
- add scripts to simplify k8s build/start/stop for local development.
- add socket client try/catch block to catch environment variable misconfiguration.
- refactor entrypoint.sh to remove passing environment variables
- refactor start.php to access environment variables using the NaadVars class.
- refactor Dockerfile to remove environment variable declarations
- add scripts to composer.json for starting the client and building the image.

### Nov 20, 2024

- Replace raw curl commands with guzzle in DestinationClient ([DESCW-2764](https://citz-gdx.atlassian.net/browse/DESCW-2764))

### Nov 19, 2024

- Add error handling for missing identifiers in alerts ([DESCW-2669](https://citz-gdx.atlassian.net/browse/DESCW-2672))

### Nov 14, 2024

- remove NaadRSSClient class
- remove get_rss module that uses it
- remove NaadRSSClientTest
- tweak test output with `--testdox` option
- [DESCW-2704](https://citz-gdx.atlassian.net/browse/DESCW-2704)

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
