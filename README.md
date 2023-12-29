# NAADS Connector
A PHP client for connecting the National Alert Aggregation & Dissemination System (NAADS) via TCP socket to a REST API.

## Usage
To build and run via Docker:
```sh
docker build -t naads .
docker run --rm -e NAADS_NAME=NAADS-1 -e NAADS_URL=streaming1.naad-adna.pelmorex.com naads
```