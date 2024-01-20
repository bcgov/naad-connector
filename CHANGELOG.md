# Changelog

## 1.0.0
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