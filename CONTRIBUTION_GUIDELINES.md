# GasoPay Backend: Contribution Guidelines

This document outlines the guidelines for contributing to the GasoPay backend project. Adhering to these guidelines will help maintain code quality, ensure a smooth development process, and facilitate collaboration.

## Table of Contents

- [GasoPay Backend: Contribution Guidelines](#gasopay-backend-contribution-guidelines)
  - [Table of Contents](#table-of-contents)
  - [1. Getting Started](#1-getting-started)
    - [1.1. Prerequisites](#11-prerequisites)
    - [1.2. Forking & Cloning](#12-forking--cloning)
    - [1.3. Setting Up Your Environment](#13-setting-up-your-environment)
  - [2. Development Process](#2-development-process)
    - [2.1. Branching Strategy](#21-branching-strategy)
    - [2.2. Making Changes](#22-making-changes)
    - [2.3. Commit Messages](#23-commit-messages)
    - [2.4. Keeping Your Branch Updated](#24-keeping-your-branch-updated)
  - [3. Coding Standards](#3-coding-standards)
    - [3.1. General PHP & Laravel](#31-general-php--laravel)
    - [3.2. Naming Conventions](#32-naming-conventions)
    - [3.3. Linting & Formatting](#33-linting--formatting)
  - [4. Testing](#4-testing)
    - [4.1. Writing Tests](#41-writing-tests)
    - [4.2. Running Tests](#42-running-tests)
  - [5. Submitting Pull Requests (PRs)](#5-submitting-pull-requests-prs)
    - [5.1. Before Submitting](#51-before-submitting)
    - [5.2. Creating a Pull Request](#52-creating-a-pull-request)
    - [5.3. PR Title and Description](#53-pr-title-and-description)
    - [5.4. Linking to Issues](#54-linking-to-issues)
  - [6. Code Review Process](#6-code-review-process)
    - [6.1. For Contributors](#61-for-contributors)
    - [6.2. For Reviewers](#62-for-reviewers)
  - [7. Issue Tracking](#7-issue-tracking)
    - [7.1. Reporting Bugs](#71-reporting-bugs)
    - [7.2. Suggesting Enhancements](#72-suggesting-enhancements)
  - [8. Communication](#8-communication)

## 1. Getting Started

### 1.1. Prerequisites

Ensure you have the following installed:

- Git
- PHP (version specified in `composer.json` or `DEVELOPMENT_WORKFLOW.md`)
- Composer
- Node.js & NPM/Yarn (if frontend assets are part of this repo, or for tools like Laravel Mix)
- Docker (recommended, refer to `DEVELOPMENT_WORKFLOW.md`)

### 1.2. Forking & Cloning

1.  **Fork** the main repository to your personal GitHub account.
2.  **Clone** your fork to your local machine:
    ```bash
    git clone https://github.com/YOUR_USERNAME/gasopay-backend.git
    cd gasopay-backend
    ```
3.  **Add the upstream remote** to keep your fork updated with the main repository:
    ```bash
    git remote add upstream https://github.com/MainAccount/gasopay-backend.git
    ```
    (Replace `MainAccount/gasopay-backend.git` with the actual upstream repository URL)

### 1.3. Setting Up Your Environment

Refer to the `DEVELOPMENT_WORKFLOW.md` document for detailed instructions on setting up your local development environment (Docker or manual).

## 2. Development Process

### 2.1. Branching Strategy

We follow a feature-branch workflow:

-   **`main` / `master`**: This branch always reflects the production-ready state. Direct commits are prohibited.
-   **`develop`**: This branch serves as an integration branch for features. All feature branches are merged into `develop`.
-   **Feature Branches**: Create a new branch for every new feature or bug fix. Name branches descriptively, e.g.:
    -   `feature/user-authentication`
    -   `fix/login-csrf-issue`
    -   `chore/update-readme`

    Branch off from the `develop` branch:
    ```bash
    git checkout develop
    git pull upstream develop
    git checkout -b feature/your-feature-name
    ```

### 2.2. Making Changes

-   Write clean, well-documented code.
-   Follow the coding standards outlined in section 3.
-   Ensure your changes do not break existing functionality.
-   Write tests for any new functionality or bug fixes (see section 4).

### 2.3. Commit Messages

Follow conventional commit message format:

-   **Format**: `<type>(<scope>): <subject>`
    -   `type`: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`
    -   `scope` (optional): Module or part of the codebase affected (e.g., `auth`, `order`, `User`)
    -   `subject`: Concise description of the change.
-   **Examples**:
    -   `feat(auth): implement OTP login`
    -   `fix(order): correct total amount calculation`
    -   `docs(readme): update setup instructions`

### 2.4. Keeping Your Branch Updated

Before submitting a PR, and periodically during development, update your feature branch with the latest changes from `develop`:

```bash
git checkout develop
git pull upstream develop
git checkout feature/your-feature-name
git rebase develop
```

Resolve any merge conflicts that arise during the rebase.

## 3. Coding Standards

### 3.1. General PHP & Laravel

-   Adhere to **PSR-12** (Extended Coding Style) and **PSR-4** (Autoloader).
-   Follow Laravel best practices and conventions (e.g., use of Facades, Service Container, Eloquent).
-   Write clear and concise comments where necessary, but avoid over-commenting obvious code.
-   Aim for readable and maintainable code.

### 3.2. Naming Conventions

Refer to `DEVELOPMENT_WORKFLOW.md` for detailed naming conventions for:

-   Variables (camelCase)
-   Methods (camelCase)
-   Classes (PascalCase)
-   Database tables (snake_case, plural)
-   Database columns (snake_case)

### 3.3. Linting & Formatting

-   Use **PHP CS Fixer** or a similar tool configured with PSR-12 standards.
-   Configure your IDE to follow these standards.
-   Ensure code is properly formatted before committing.

## 4. Testing

### 4.1. Writing Tests

-   All new features must be accompanied by unit and/or feature tests.
-   Bug fixes should include tests that reproduce the bug and verify the fix.
-   Aim for high test coverage.
-   Tests should be written using PHPUnit, following Laravel's testing helpers and structure.
-   Refer to existing tests (e.g., `PaymentHistoryControllerTest.php`) for examples.

### 4.2. Running Tests

Run the test suite using the following Artisan command:

```bash
php artisan test
```

Ensure all tests pass before submitting a pull request.

## 5. Submitting Pull Requests (PRs)

### 5.1. Before Submitting

-   Ensure your branch is up-to-date with `develop` (rebase).
-   Run all tests and ensure they pass.
-   Lint and format your code.
-   Make sure your commit messages are descriptive.

### 5.2. Creating a Pull Request

1.  Push your feature branch to your forked repository:
    ```bash
    git push origin feature/your-feature-name
    ```
2.  Go to the main GasoPay backend repository on GitHub.
3.  You should see a prompt to create a Pull Request from your recently pushed branch. Click it.
4.  Ensure the base branch is `develop` and the head branch is your feature branch.

### 5.3. PR Title and Description

-   **Title**: Clear and concise, summarizing the changes. Can follow the commit message format (e.g., `feat(auth): Add social login`).
-   **Description**: Detail the changes made:
    -   What problem does this PR solve?
    -   What are the main changes?
    -   How can the changes be tested?
    -   Include screenshots or GIFs if applicable (for UI changes, though less relevant for pure backend).

### 5.4. Linking to Issues

If your PR addresses an existing issue, link it in the PR description using keywords like `Closes #123`, `Fixes #456`.

## 6. Code Review Process

### 6.1. For Contributors

-   Be prepared to answer questions and make changes based on feedback.
-   Respond to review comments promptly.
-   If you make updates to your PR, re-request a review if necessary.

### 6.2. For Reviewers

-   Be constructive and respectful.
-   Focus on code quality, correctness, performance, and adherence to guidelines.
-   Provide clear and actionable feedback.
-   Test the changes locally if possible, especially for complex features.
-   Approve the PR when you are satisfied with the changes.

## 7. Issue Tracking

Use the GitHub Issues section of the main repository for tracking bugs and feature requests.

### 7.1. Reporting Bugs

When reporting a bug, please include:

-   A clear and descriptive title.
-   Steps to reproduce the bug.
-   Expected behavior.
-   Actual behavior.
-   Your environment details (PHP version, OS, etc.).
-   Any relevant error messages or logs.

### 7.2. Suggesting Enhancements

When suggesting an enhancement, please include:

-   A clear and descriptive title.
-   A detailed explanation of the proposed enhancement.
-   The problem it solves or the value it adds.
-   Potential implementation ideas (optional).

## 8. Communication

-   For discussions related to specific issues or PRs, use the comments section on GitHub.
-   For general development discussions, use the designated communication channels (e.g., Slack, Microsoft Teams, as decided by the team).

By following these guidelines, we can work together to build a robust and maintainable GasoPay backend. Thank you for your contributions! 