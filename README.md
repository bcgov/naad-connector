# NAAD Connector

A PHP client for connecting the National Alert Aggregation & Dissemination (NAAD) System via TCP socket to a REST API.

## Usage

### Local

#### Docker deployment

```zsh
# build only
composer build

# build and run
composer start
```

- note: _It is no longer possible to run this app from docker because the database requires either docker-compose or K8s deployments in order to manage database migrations and logging._

- This leverages a secret that extracts `DESTINATION_PASSWORD` from your .env file.

#### Docker-Compose deployment

- Prerequisites: rename the `./sample-env` to `.env` and fill in the values for local use only.

```shell
docker compose up
```

#### Docker Desktop Kubernetes deployment

To build and run in Kubernetes via Docker Desktop:
Note: Kubernetes must be enabled in Docker Desktop.

```sh
kubectl config use-context docker-desktop
docker build -t bcgovgdx/naad-app .
kubectl apply -k deployments/kustomize/overlays/local
```

PHPMyAdmin will then be accessible at <http://0.0.0.0:31008>. You may need to use Firefox or Safari to access this as Chrome may block this address due to it not using https.

### OpenShift Build

```sh
# Change nameplate
oc project 12345-tools
oc apply -k deployments/kustomize/image-builds --namespace=12345-tools
oc start-build naad-app --follow --namespace=12345-tools
```

### OpenShift Deploy

```sh
oc project 12345-tools
oc apply -k deployments/kustomize/base --namespace=12345-tools
```

### View the database tables (Local only)

- visit <http://0.0.0.0:8082> to see the phpMyAdmin page for the naad_connector database. It includes the latest migrations and all alerts that have been recorded.

_note:  this is mapped to port 8082 to avoid conflict with our wordpress containers_
