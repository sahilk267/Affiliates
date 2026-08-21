# Repository License Decision Record

## Status

**Pending repository-owner decision.**

## Evidence reviewed

The repository contains no `LICENSE`, `COPYING`, `NOTICE`, or equivalent legal declaration. `composer.json` does not contain a `license` field. The README now states that no license has been declared and that the repository owner must confirm the appropriate SPDX classification before external distribution.

## Decision boundary

No license identifier is being added automatically. Selecting `MIT`, `proprietary`, or another SPDX value would create a legal assertion that is not supported by the repository evidence or an explicit owner instruction. The Composer warning is therefore retained as a deliberate metadata exception rather than hidden with an invented value.

## Required owner action

Before publishing the package externally or distributing it beyond the authorized organization, the repository owner must select one of the following documented outcomes:

| Outcome | Required action |
|---|---|
| Open-source distribution | Add an approved SPDX identifier to `composer.json`, add the corresponding license text, and obtain legal or owner approval. |
| Closed-source/internal distribution | Set the Composer license to `proprietary` only if the owner confirms that classification and ensure repository access and distribution controls match it. |
| Deferred decision | Keep the warning, record an owner and due date, and block external distribution until the decision is made. |

## Verification

The metadata warning does not affect dependency resolution, Composer audit, application tests, migrations, or runtime behavior. It remains a release-governance item and must not be silently classified by automation.
