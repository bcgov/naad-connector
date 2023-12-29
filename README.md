# NAAD Connector
A PHP client for connecting the National Alert Aggregation & Dissemination (NAAD) System via TCP socket to a REST API.

## Usage
To build and run via Docker:
```sh
docker build -t naad .
docker run --rm -e NAAD_NAME=NAADS-1 -e NAAD_URL=streaming1.naad-adna.pelmorex.com naad
```