# GasoPay Backend: User Stories

This document outlines user stories from a backend perspective, detailing system functionalities required for different user roles and interactions.

## Table of Contents

- [GasoPay Backend: User Stories](#gasopay-backend-user-stories)
  - [Table of Contents](#table-of-contents)
  - [1. Introduction](#1-introduction)
  - [2. User Roles Perspective](#2-user-roles-perspective)
    - [2.1. As a Super Administrator (Backend System)](#21-as-a-super-administrator-backend-system)
    - [2.2. As an Administrator (Backend System)](#22-as-an-administrator-backend-system)
    - [2.3. As a Branch Administrator (Backend System)](#23-as-a-branch-administrator-backend-system)
    - [2.4. As a Rider (Backend System)](#24-as-a-rider-backend-system)
    - [2.5. As a Regular User/Customer (Backend System)](#25-as-a-regular-usercustomer-backend-system)
    - [2.6. As an Unauthenticated Guest (Backend System)](#26-as-an-unauthenticated-guest-backend-system)
  - [3. Epic/Feature Based User Stories (Backend Focus)](#3-epicfeature-based-user-stories-backend-focus)
    - [3.1. Authentication & Authorization](#31-authentication--authorization)
    - [3.2. User Account Management](#32-user-account-management)
    - [3.3. Product Management](#33-product-management)
    - [3.4. Branch Management](#34-branch-management)
    - [3.5. Order Processing](#35-order-processing)
    - [3.6. Payment Handling](#36-payment-handling)
    - [3.7. Notifications](#37-notifications)

## 1. Introduction

User stories help define system behavior and ensure that development aligns with the needs of various actors interacting with the backend. These stories focus on the *backend's capabilities* and *responsibilities*.

## 2. User Roles Perspective

### 2.1. As a Super Administrator (Backend System)

- I need to be able to create, view, update, and delete Administrator accounts, so I can manage system administrators.
- I need to be able to create, view, update, and delete Branches, so I can manage operational locations.
- I need to be able to create, view, update, and delete Products, so I can manage the product catalog.
- I need to be able to view all users, orders, and payment histories across the system, so I have a complete overview of operations.
- I need to be able to ban/unban any user (except other SuperAdmins), so I can manage problematic accounts.
- I need to be able to configure system-wide settings (e.g., payment gateway details, notification templates - *if applicable*), so I can customize the platform.
- I need to be able to access comprehensive analytics and reports, so I can monitor business performance.
- I need to be able to manage all user roles and permissions, so I can ensure proper access control.

### 2.2. As an Administrator (Backend System)

- I need to be able to view and manage users within my scope (e.g., specific branches or all non-SuperAdmins), so I can support user operations.
- I need to be able to view orders and payment histories, so I can oversee transactions.
- I need to be able to approve/reject rider verification requests, so I can onboard new riders.
- I need to be able to ban/unban users (non-Admins/SuperAdmins), so I can manage user access.
- I need to be able to manage payment proofs (approve/reject), so I can validate transactions.
- I need to be able to mark cash payments as received, so I can update order statuses.

### 2.3. As a Branch Administrator (Backend System)

- I need to be able to view and manage riders associated with my branch, so I can manage local delivery staff.
- I need to be able to approve/reject rider verification requests for my branch, so I can control local rider onboarding.
- I need to be able to view orders originating from my branch, so I can monitor local sales.
- I need to be able to view payment histories for my branch, so I can track local revenue.
- I need to be able to create orders on behalf of customers/riders for my branch, so I can facilitate sales.
- I need to be able to update the status of orders from my branch, so I can reflect their current state.
- I need to be able to view dashboard statistics specific to my branch (sales, riders, etc.), so I can assess performance.
- I need to be able to manage product price quotes or specific product availability for my branch, so I can handle local product variations.
- I need to be able to process QR code scans for order fulfillment or payment verification at my branch, so I can streamline operations.

### 2.4. As a Rider (Backend System)

- I need to be able to register for a rider account, providing necessary profile and verification details, so I can apply to be a rider.
- I need to be ableto log in and manage my API token, so I can securely access my rider-specific functionalities.
- I need to be able to update my profile information (e.g., vehicle, address), so my details are current.
- I need to receive notifications (SMS/Push) about my account status (verification, orders), so I am kept informed.
- I need to be able to view orders assigned to me, so I know what to deliver (*if order assignment is a feature*).
- I need to be able to update the status of my assigned deliveries (e.g., picked up, delivered - *if applicable*), so the system reflects real-time progress.
- I need to be able to view my payment history/earnings, so I can track my income.
- I need my balance to be correctly updated based on orders and payments, so my account is accurate.

### 2.5. As a Regular User/Customer (Backend System)

- I need to be able to register for an account, so I can use GasoPay services.
- I need to be able to log in and manage my API token, so I can securely access my account.
- I need to be able to update my profile information, so my details are accurate.
- I need to be able to view products and their prices, so I can make purchasing decisions.
- I need to be able to place an order for a product from a specific branch, so I can buy gas.
- I need to be able to view my order history, so I can track my purchases.
- I need to be able to make payments for my orders (e.g., submit payment proof), so I can complete my transactions.
- I need to receive notifications about my order status (e.g., confirmed, paid, out for delivery), so I am kept informed.
- I need to be able to view my payment history, so I can manage my expenses.

### 2.6. As an Unauthenticated Guest (Backend System)

- I need to be able to register for a new user account, so I can start using the service.
- I need to be able to log in if I already have an account, so I can access authenticated features.
- I *may* need to be able to view publicly available information (e.g., list of products, branches - *if API allows unauthenticated access to these*), so I can learn about GasoPay before registering.

## 3. Epic/Feature Based User Stories (Backend Focus)

### 3.1. Authentication & Authorization

- **Story:** The system must securely register new users with different roles.
- **Story:** The system must authenticate users via API tokens (email/phone and password login).
- **Story:** The system must allow users to securely log out, invalidating their current token.
- **Story:** The system must restrict access to endpoints based on user roles (SuperAdmin, Admin, BranchAdmin, User, Rider).

### 3.2. User Account Management

- **Story:** The system must allow SuperAdmins/Admins to create and manage other admin/user accounts.
- **Story:** The system must allow users to update their own profile information.
- **Story:** The system must handle rider registration, including detailed profile information (NIN, guarantors, vehicle).
- **Story:** The system must support a verification process for riders, manageable by Admins.
- **Story:** The system must allow authorized admins to ban or suspend user accounts.

### 3.3. Product Management

- **Story:** The system must allow SuperAdmins to perform CRUD operations on the product catalog.
- **Story:** The system must allow authenticated users to view available products and their details.

### 3.4. Branch Management

- **Story:** The system must allow SuperAdmins to perform CRUD operations on branches.
- **Story:** The system must associate users (staff, admins) with branches.
- **Story:** The system must provide branch-specific data views for Branch Admins (dashboard, orders, riders).

### 3.5. Order Processing

- **Story:** The system must allow authenticated users (customers, or branch admins on their behalf) to create orders.
- **Story:** The system must associate orders with a user, a branch, and product(s)/product type.
- **Story:** The system must track the financial status of an order (amount due, amount paid, payment status).
- **Story:** The system must allow authorized users to update order details or status.
- **Story:** The system must correctly update rider balances based on order payments and amounts due.

### 3.6. Payment Handling

- **Story:** The system must record payment history for orders, including amount, method, and status.
- **Story:** The system must allow users to submit proof of payment for certain payment methods.
- **Story:** The system must allow authorized admins to approve or reject submitted payment proofs.
- **Story:** The system must allow authorized admins to mark cash payments as received and update relevant statuses.

### 3.7. Notifications

- **Story:** The system must send SMS notifications for key events (e.g., rider registration, order creation for rider, verification status changes).
- **Story:** The system must send email notifications for key events (e.g., user registration with initial credentials).

This list is not exhaustive but covers the core backend functionalities implied by the codebase structure. 