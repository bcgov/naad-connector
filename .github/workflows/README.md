# Workflows

## PR Open
When a pull request is opened or changed, linting and unit testing are performed, then a new image is built and hosted on the [GitHub Container Registry](https://github.com/bcgov/naad-connector/pkgs/container/naad-connector%2Fnaad-connector). Its version will be set to the PR number, eg. if the PR number is #101, a `101` version of the image will be built. It will also be given the `dev` tag. Every time the PR is changed the build is triggered again and the version is overwritten.

## Merge
When a pull request is merged into the `main` branch, the current `dev` image is given the `test` and `latest` tags.

**NOTE:** If multiple PRs are open at the same time the `dev`-tagged image may not be the image that was built from the PR that was merged if another PR has been changed after the merge was performed. We should avoid opening multiple PRs at the same time.

## Release
When a release is created, the current `latest` tag is given the `prod` and a version tag using the release's version tag, eg. if a v2.1.1 release is created, the `latest` image will also be tagged with `v2.1.1`.

**NOTE:** Ensure that the Merge workflow has fully completed and the correct image has been given the `latest` tag before creating the release or the wrong image could be given the release tag due to timing issues.
