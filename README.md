# NAAD Connector

A PHP client for connecting the National Alert Aggregation & Dissemination (NAAD) System via TCP socket to a REST API.

## Usage

### Local Deployment

#### Devcontainer

To run in the devcontainer (see .devcontainer/ directory):

1. Install the Dev Containers VSCode extension.
1. Run `cp .devcontainer/sample.override.env .devcontainer/override.env` and add/remove any env variable overrides you need based on your environment.
    - Note: this step can be skipped if you do'ot need to override any env variables.
1. View > Command Palette... > Dev Containers: Reopen in Container
1. VSCode should relaunch and the terminal should look like this: `vscode ➜ /workspaces/naad-connector`
1. This terminal should allow most commands to be run without any further dev environment setup:
    - `php src/start.php` to start the application
    - `composer test` to run unit tests
    - `composer test-coverage` to generate unit test coverage reports
    - `composer phpcs` to run linting
    - `composer migrate` to run database migrations
    - `composer phpdoc` to generate project documentation

#### Docker Compose deployment

- Prerequisites: rename the `./sample-env` to `.env` and fill in the values for local use only.

```shell
# build and run
docker compose up --build

# run using known good app image build
docker compose up
```

#### Docker Desktop Kubernetes deployment

To build and run in Kubernetes via Docker Desktop, follow these steps:

```shell
kubectl config use-context docker-desktop
docker build -t bcgovgdx/naad-app .
kubectl apply -k deployments/kustomize/overlays/local

# or use composer instead:
composer k8s-start
```

**Note:** Kubernetes must be enabled in Docker Desktop.

Stopping (deleting) all pods will destroy all alerts in the database. To stop all pods, run:

```shell
kubectl delete -k deployments/kustomize/overlays/local

# or use composer instead:
composer k8s-delete
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

```shell
# Change to the correct namespace
oc project 12345-tools

# Apply the image builds configuration
oc apply -k deployments/kustomize/image-builds --namespace=12345-tools

# Start the build process and follow the output
oc start-build naad-app --follow --namespace=12345-tools
```

#### OpenShift Deploy

```shell
# Change to the correct namespace
oc project 12345-tools

# Apply the base configuration
oc apply -k deployments/kustomize/base --namespace=12345-tools
```

### Generate & View Documentation with phpDocumentor

The only command you need is `composer phpdoc`. It runs a script that pulls and runs the phpdoc container, generates documentation, and opens a browser so you can view it assuming you run the command from the root directory of your project.

#### Configuration

To configure the phpdoc further, look at `phpdoc.dist.xml` in the root directory of the project. For now it only determines the output folder `/docs` where the `index.html` is found, and the source folder(s) where the code that will be documented is found.

#### Viewing Documentation

To view the documentation, you can use one of the following methods:

- Run `composer phpdoc-view`
- Navigate to `./docs` in your console and type `open index.html`
