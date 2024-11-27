# NAAD Connector

A PHP client for connecting the National Alert Aggregation & Dissemination (NAAD) System via TCP socket to a REST API.

## Usage

### Local Deployment

#### Docker deployment

```zsh
# build only
composer build

# build and run
composer start
```

- note: _It is no longer possible to run this app from docker because the database requires either docker-compose or K8s deployments in order to manage database migrations and logging._

#### Docker Compose deployment

- Prerequisites: rename the `./sample-env` to `.env` and fill in the values for local use only.

```shell
docker compose up
```


#### Docker Desktop Kubernetes Deployment

To build and run in Kubernetes via Docker Desktop, follow these steps:

```sh
kubectl config use-context docker-desktop
docker build -t bcgovgdx/naad-app .
kubectl apply -k deployments/kustomize/overlays/local
# or use composer instead:
composer startpods
```

**Note:** Kubernetes must be enabled in Docker Desktop.

Stopping all pods will destroy all alerts in the database. To stop all pods, run:

```sh
kubectl delete -k deployments/kustomize/overlays/local
# or use composer instead:
composer stoppods
```
---

### Accessing PHPMyAdmin to view your alerts table

After deployment, PHPMyAdmin will be accessible at the following local addresses:

- Docker Compose: <http://0.0.0.0:8080>
- Kubernetes: <http://0.0.0.0:31008>

**Note:** You may need to use Firefox or Safari to access this as Chrome may block this address due to it not using https.

---

### Remote Deployment

#### OpenShift Build

```sh
# Change to the correct namespace
oc project 12345-tools

# Apply the image builds configuration
oc apply -k deployments/kustomize/image-builds --namespace=12345-tools

# Start the build process and follow the output
oc start-build naad-app --follow --namespace=12345-tools
```


#### OpenShift Deploy

```sh
# Change to the correct namespace
oc project 12345-tools

# Apply the base configuration
oc apply -k deployments/kustomize/base --namespace=12345-tools
```
