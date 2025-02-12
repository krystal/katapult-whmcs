# Changelog

## [2.0.4](https://github.com/krystal/katapult-whmcs/compare/v2.0.3...v2.0.4) (2025-02-12)


### Miscellaneous Chores

* improve error handling in Test Connection and add signposting ([26647e6](https://github.com/krystal/katapult-whmcs/commit/26647e6d09a81ad3679d9aa1133144bf5bd4a5a8))

## [2.0.3](https://github.com/krystal/katapult-whmcs/compare/v2.0.2...v2.0.3) (2025-02-03)


### Miscellaneous Chores

* **CI:** fix release-please CI output ([b561291](https://github.com/krystal/katapult-whmcs/commit/b561291b8688124388dd7e12e0afcd1d4625900d))

## [2.0.2](https://github.com/krystal/katapult-whmcs/compare/v2.0.1...v2.0.2) (2025-02-03)


### Bug Fixes

* don't auto-install a psr7, remove guzzle on build ([b29721e](https://github.com/krystal/katapult-whmcs/commit/b29721e57fc1d7c96b77c51dd088ce8cb76b8e98))
* lock psr/http-message to v1.0.1 ([dc85484](https://github.com/krystal/katapult-whmcs/commit/dc85484f43288e9637c9c156255013d1a739e1ce))


### Miscellaneous Chores

* **build:** remove .github directory from built zip ([2846e7b](https://github.com/krystal/katapult-whmcs/commit/2846e7bef9fb644eec609a678c0f5cc838d7c693))
* **docs:** update link to docs ([b962281](https://github.com/krystal/katapult-whmcs/commit/b9622816c87cc3df7a537fda90b17a131f9b1b2b))
* Stan baseline ([e3a77ce](https://github.com/krystal/katapult-whmcs/commit/e3a77ce683366a41a5d3a8c7a3cff874799cf78f))
* update formatting and remove some additional files from the zip ([c9a3c9e](https://github.com/krystal/katapult-whmcs/commit/c9a3c9e2af6bffc5d29da52295dd420c3e5bff4b))
* use nyholm psr7 in dev ([f6b1655](https://github.com/krystal/katapult-whmcs/commit/f6b16552a780fa922c456df37ee53e7070607e2e))

## [2.0.1](https://github.com/krystal/katapult-whmcs/compare/v2.0.0...v2.0.1) (2025-02-03)


### Miscellaneous Chores

* **CI:** build the zip and attch to release ([9225057](https://github.com/krystal/katapult-whmcs/commit/9225057f5fe0521dd18fef04a75618396f7a5a9b)), closes [#29](https://github.com/krystal/katapult-whmcs/issues/29)

## [2.0.0](https://github.com/krystal/katapult-whmcs/compare/v1.2.1...v2.0.0) (2024-10-25)


### ⚠ BREAKING CHANGES

* convert API usages to Katapult API v3

### Features

* add a stan baseline and upgrade to Katapult API 4.0 ([f0e30d0](https://github.com/krystal/katapult-whmcs/commit/f0e30d0db17860e431d0995d5a74bd560cfee304))
* add the Test Connection option ([5db851b](https://github.com/krystal/katapult-whmcs/commit/5db851b963a43ecb1a99cdddae76013d9bfe4a3a))
* API v5 ([48605cd](https://github.com/krystal/katapult-whmcs/commit/48605cd9a30928a544edf4c268a0527bc2f6d864))
* custom disk size option for system disk ([cbe863e](https://github.com/krystal/katapult-whmcs/commit/cbe863e6a123580afc398558c76aad5a25695aa6))
* custom disk size validation in cart ([21a0fd3](https://github.com/krystal/katapult-whmcs/commit/21a0fd30d65a86071d1cf069a83384ea6b862ebb))
* delete disk backup policies on terminate. closes [#22](https://github.com/krystal/katapult-whmcs/issues/22) ([4fbc201](https://github.com/krystal/katapult-whmcs/commit/4fbc201f479b366e0a74451bd3e941aeda5a9c68))
* set custom disk size ([73df028](https://github.com/krystal/katapult-whmcs/commit/73df0282e900e944a634dd0ff38993b954ecd3fd))
* support drop down for disk size ([025a5af](https://github.com/krystal/katapult-whmcs/commit/025a5af74aa20ed646f65ae7e3e884802e81966d))
* upgrade to Katapult API client v4.0 ([#33](https://github.com/krystal/katapult-whmcs/issues/33)) ([a63dd4e](https://github.com/krystal/katapult-whmcs/commit/a63dd4e06129b9e3286a7117b01914b3769375d8))
* upgrade to katapult-php 5.1.0 ([dd2b356](https://github.com/krystal/katapult-whmcs/commit/dd2b356df0717b9eded041b5b4eca2e0dcf14f4e))


### Bug Fixes

* add 5s timeout back into API ([559452e](https://github.com/krystal/katapult-whmcs/commit/559452e30a735935f3c693a0c7c82af4eb2cc23c))
* add detail to error messages ([2ea85c5](https://github.com/krystal/katapult-whmcs/commit/2ea85c50b2e5c5b8e9a480d0e6cf04439e50ff46))
* API response types, improve error handling ([290e4d4](https://github.com/krystal/katapult-whmcs/commit/290e4d4e7176f800b4e4992128f204b19cb69e63))
* ci job name conflict ([79899fb](https://github.com/krystal/katapult-whmcs/commit/79899fbcd8fc8d02e25a15861bee9b0caf9b51df))
* CI test job ([fd225c5](https://github.com/krystal/katapult-whmcs/commit/fd225c56983a6f331df04b55601a659d693566f8))
* deps to php 8.1, fix validate config ([d146f13](https://github.com/krystal/katapult-whmcs/commit/d146f1387fae5d08f8a603054c24ec0f8272cc74))
* error message wording ([6c2a505](https://github.com/krystal/katapult-whmcs/commit/6c2a5059cf376ebadf3cfa35442514cc40d621dd))
* new class for vm package ([e307506](https://github.com/krystal/katapult-whmcs/commit/e30750634fc0c0de3ac44a381bc58e908cf5a6cd))
* not loading JS into admin/client area ([455ea38](https://github.com/krystal/katapult-whmcs/commit/455ea386a37c8fc70fbe3e4f1e07ea120361e948))
* phpcs ([6c6a24e](https://github.com/krystal/katapult-whmcs/commit/6c6a24ec99e1e943c4c40a346a2c8f7099fc260f))
* VM state indicator ([ea30a7f](https://github.com/krystal/katapult-whmcs/commit/ea30a7f9f035ecfb91decfbf70234f6ebaed44bb))


### Miscellaneous Chores

* add a check on the current VM build state directly ([5150222](https://github.com/krystal/katapult-whmcs/commit/5150222e38c93f44e8d05de7481d81639b2e2fb9))
* add docs from sample module, reorder ([50ad3a3](https://github.com/krystal/katapult-whmcs/commit/50ad3a39799c79dea702bfb19260249f6d692381))
* add ide-helper for WHMCS functions ([39e45ba](https://github.com/krystal/katapult-whmcs/commit/39e45bafae17b89541dc3310eb2d301a03cfa5d5))
* add phpstan with a baseline ([410f9cf](https://github.com/krystal/katapult-whmcs/commit/410f9cf4c91791bc3aeb92de6a1fa876a343b642))
* add phpunit to CI ([9115d9e](https://github.com/krystal/katapult-whmcs/commit/9115d9e296837a092baf0379ae6f99de2919f2d5))
* apply phpcs ([13087c9](https://github.com/krystal/katapult-whmcs/commit/13087c90933d89fff92b591ecaa824e27e250dec))
* **CI:** add linter to GHA ([3514c22](https://github.com/krystal/katapult-whmcs/commit/3514c22a7c912f49b4fa81b44401bfd211c3ba88))
* consolidate CI configs ([4b25911](https://github.com/krystal/katapult-whmcs/commit/4b25911020c21a780682e563435be198779cf536))
* **docs:** update and add Vale ([44dab5c](https://github.com/krystal/katapult-whmcs/commit/44dab5c687d1f4df65fc9bea4f54d425f032db1d))
* **docs:** update Readme to new min PHP version ([ff72029](https://github.com/krystal/katapult-whmcs/commit/ff72029bf4124c405ec54da8fed08df79cd74c1c))
* handle the failed state before the complete state for a VM build ([e90366b](https://github.com/krystal/katapult-whmcs/commit/e90366bea233eb4b84e0f3e8e276d98f163080bf))
* link to new docs site ([c9cb5cd](https://github.com/krystal/katapult-whmcs/commit/c9cb5cde91ca4e1535d103d81a52b29d8edbaa4c))
* makefile for code cov ([e71b73e](https://github.com/krystal/katapult-whmcs/commit/e71b73e281f0085ca97480a5496afe125e96f728))
* **phpcs:** fix cs issue ([c333660](https://github.com/krystal/katapult-whmcs/commit/c3336603b199e9bdc6c6a7c9637b7122ff6572a4))
* remove additional log output ([d5844e1](https://github.com/krystal/katapult-whmcs/commit/d5844e181f6dbf037cd2cf3abc6952f718825aa0))
* remove disallowed attribute ([3ec7082](https://github.com/krystal/katapult-whmcs/commit/3ec70827f4cc92498d6c47ca0734ed07c791d4af))
* remove unused import ([18c8d73](https://github.com/krystal/katapult-whmcs/commit/18c8d736abe76463a9f4cb52c4cecf9b9345a97b))
* review stan issues and update baseline ([c2d0bf6](https://github.com/krystal/katapult-whmcs/commit/c2d0bf6c4014941df7b12d7c9f74f4d05f511350))
* update Stan's baseline ([ba56744](https://github.com/krystal/katapult-whmcs/commit/ba5674472400929fb49e4efbe552b28c02d71761))


### Code Refactoring

* convert API usages to Katapult API v3 ([d82e9a6](https://github.com/krystal/katapult-whmcs/commit/d82e9a6e14ea02ef36addf774dfe010b5bf0b0e3))

## [1.2.1](https://github.com/krystal/katapult-whmcs/compare/v1.2.0...v1.2.1) (2024-01-12)


### Bug Fixes

* add whmcs.json file ([9a95f13](https://github.com/krystal/katapult-whmcs/commit/9a95f13dc4636c19b7a7fcede2043e2ad2905797))


### Miscellaneous Chores

* add CODEOWNERS for auto PR-review tagging ([ba99c1d](https://github.com/krystal/katapult-whmcs/commit/ba99c1d2bcf13596367fa77f15648bef5e8f807c))
* add release-please ([3c2a178](https://github.com/krystal/katapult-whmcs/commit/3c2a1786638218605c2dfea7ba361aa3f4af3e37))
* **release-please:** fix inability for rp to parse version string ([931ee6a](https://github.com/krystal/katapult-whmcs/commit/931ee6a1e92eefc8c28ac54643bcc5c08120758a))
* remove files from readme example structure ([7e57fe3](https://github.com/krystal/katapult-whmcs/commit/7e57fe34c0ac200bfbbb4880c233512eebd45e40))
