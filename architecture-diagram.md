# Inventory Management System - Software Architecture Diagram

## System Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                    INVENTORY MANAGEMENT SYSTEM                      │
│                         (PHP + MySQL)                               │
└─────────────────────────────────────────────────────────────────────┘
```

## 1. Three-Tier Architecture

```
┌──────────────────────────────────────────────────────────────────────┐
│                        PRESENTATION LAYER                            │
│                         (User Interface)                             │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐                │
│  │   index.php │  │  login.php  │  │ logout.php  │                │
│  │  (Router)   │  │             │  │             │                │
│  └─────────────┘  └─────────────┘  └─────────────┘                │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────┐       │
│  │            ADMIN INTERFACE                               │       │
│  │  ┌─────────────────┐  ┌───────────────────────────┐    │       │
│  │  │ dashboard_m.php │  │    FEATURES MODULE         │    │       │
│  │  │  (Manager)      │──┤ • inventory.php            │    │       │
│  │  │                 │  │ • sales.php (POS)          │    │       │
│  │  │ • Analytics     │  │ • purchase.php             │    │       │
│  │  │ • All Features  │  │ • sales_history.php        │    │       │
│  │  │ • Settings      │  │ • purchase_history.php     │    │       │
│  │  └─────────────────┘  │ • analytics.php            │    │       │
│  │                       │ • refunds.php              │    │       │
│  │                       └───────────────────────────┘    │       │
│  └─────────────────────────────────────────────────────────┘       │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────┐       │
│  │            STAFF INTERFACE                               │       │
│  │  ┌─────────────────┐  ┌───────────────────────────┐    │       │
│  │  │ dashboard_s.php │  │    FEATURES MODULE         │    │       │
│  │  │  (Staff)        │──┤ • inventory_s.php          │    │       │
│  │  │                 │  │ • sales.php (POS)          │    │       │
│  │  │ • View Only     │  │ • sales_history.php        │    │       │
│  │  │ • Sales         │  │                            │    │       │
│  │  └─────────────────┘  └───────────────────────────┘    │       │
│  └─────────────────────────────────────────────────────────┘       │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────┐       │
│  │            SHARED COMPONENTS                             │       │
│  │  • partials/ai_chat_widget.php                          │       │
│  │  • partials/footer.php                                   │       │
│  │  • css/style.css                                         │       │
│  └─────────────────────────────────────────────────────────┘       │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌──────────────────────────────────────────────────────────────────────┐
│                         BUSINESS LOGIC LAYER                         │
│                        (Processing & Logic)                          │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌─────────────────────────────────────────────────────────┐       │
│  │         CONNECTION MODULE (connection/)                  │       │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────────┐          │       │
│  │  │ auth.php │  │  db.php  │  │ refunds.php  │          │       │
│  │  │          │  │  (PDO)   │  │              │          │       │
│  │  └──────────┘  └──────────┘  └──────────────┘          │       │
│  └─────────────────────────────────────────────────────────┘       │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────┐       │
│  │         BUSINESS LOGIC MODULE (logics/)                  │       │
│  │  ┌──────────────┐  ┌──────────────────┐                │       │
│  │  │   add.php    │  │  update_stock.php│                │       │
│  │  │  (Add Items) │  │  (Update Stock)  │                │       │
│  │  └──────────────┘  └──────────────────┘                │       │
│  │  ┌──────────────┐  ┌──────────────────┐                │       │
│  │  │ delete.php   │  │  ai_chat.php     │                │       │
│  │  │ (Del Items)  │  │  (AI Assistant)  │                │       │
│  │  └──────────────┘  └──────────────────┘                │       │
│  │  ┌─────────────────────┐  ┌─────────────────────┐      │       │
│  │  │ refund_request.php  │  │ refund_action.php   │      │       │
│  │  │ (Request Refund)    │  │ (Approve/Reject)    │      │       │
│  │  └─────────────────────┘  └─────────────────────┘      │       │
│  └─────────────────────────────────────────────────────────┘       │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌──────────────────────────────────────────────────────────────────────┐
│                          DATA LAYER                                  │
│                         (MySQL Database)                             │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌──────────────────────────────────────────────────────┐          │
│  │                    DATABASE: ims                      │          │
│  │                                                       │          │
│  │  ┌──────────┐  ┌──────────────┐  ┌───────────────┐ │          │
│  │  │  users   │  │   products   │  │     bills     │ │          │
│  │  │          │  │              │  │               │ │          │
│  │  │ • id     │  │ • id         │  │ • id          │ │          │
│  │  │ • user   │  │ • name       │  │ • customer    │ │          │
│  │  │ • pass   │  │ • category   │  │ • total       │ │          │
│  │  │ • role   │  │ • quantity   │  │ • date        │ │          │
│  │  └──────────┘  │ • price      │  └───────────────┘ │          │
│  │                │ • reorder_lvl│                     │          │
│  │                │ • image      │  ┌───────────────┐ │          │
│  │                └──────────────┘  │  bill_items   │ │          │
│  │                                  │               │ │          │
│  │  ┌──────────────┐               │ • bill_id     │ │          │
│  │  │supply_orders │               │ • product_id  │ │          │
│  │  │              │               │ • quantity    │ │          │
│  │  │ • id         │               │ • price       │ │          │
│  │  │ • supplier   │               └───────────────┘ │          │
│  │  │ • total      │                                 │          │
│  │  │ • date       │  ┌──────────────┐              │          │
│  │  └──────────────┘  │   refunds    │              │          │
│  │                    │              │              │          │
│  │  ┌──────────────┐  │ • id         │              │          │
│  │  │supply_items  │  │ • bill_id    │              │          │
│  │  │              │  │ • amount     │              │          │
│  │  │ • supply_id  │  │ • reason     │              │          │
│  │  │ • product_id │  │ • status     │              │          │
│  │  │ • quantity   │  │ • date       │              │          │
│  │  │ • price      │  └──────────────┘              │          │
│  │  └──────────────┘                                │          │
│  │                                                   │          │
│  └──────────────────────────────────────────────────┘          │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘
```

## 2. User Flow Diagram

```
                          ┌──────────────┐
                          │  index.php   │
                          │   (Entry)    │
                          └──────┬───────┘
                                 │
                    ┌────────────┴────────────┐
                    │  Session Check          │
                    └────────────┬────────────┘
                                 │
                    ┌────────────┴────────────┐
                    │                         │
              Not Logged In              Logged In
                    │                         │
                    ▼                         ▼
            ┌──────────────┐        ┌─────────────────┐
            │  login.php   │        │  Role Check     │
            │              │        └────────┬────────┘
            │ • Username   │                 │
            │ • Password   │     ┌───────────┴───────────┐
            └──────┬───────┘     │                       │
                   │          role='admin'          role='staff'
                   │              │                       │
            ┌──────▼──────┐       ▼                       ▼
            │  auth.php   │  ┌──────────────┐    ┌──────────────┐
            │  (Validate) │  │dashboard_m   │    │dashboard_s   │
            └─────────────┘  │  (Admin)     │    │  (Staff)     │
                             └──────┬───────┘    └──────┬───────┘
                                    │                   │
                    ┌───────────────┴────┬──────────────┤
                    │                    │              │
                    ▼                    ▼              ▼
          ┌──────────────────┐  ┌──────────────┐  ┌──────────┐
          │  ADMIN FEATURES  │  │ SHARED FEAT. │  │ LIMITED  │
          │                  │  │              │  │ FEATURES │
          │ • Inventory Mgmt │  │ • Sales/POS  │  │          │
          │ • Purchase Orders│  │ • View Sales │  │ • View   │
          │ • Refund Approval│  │              │  │ • Sales  │
          │ • Analytics      │  │              │  │          │
          │ • Settings       │  │              │  │          │
          │ • All History    │  │              │  │          │
          └──────────────────┘  └──────────────┘  └──────────┘
```

## 3. Feature Module Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                      FEATURE MODULES                            │
└─────────────────────────────────────────────────────────────────┘

┌──────────────────┐     ┌──────────────────┐     ┌──────────────┐
│   INVENTORY      │     │    SALES (POS)   │     │   PURCHASE   │
│   MANAGEMENT     │     │                  │     │   ORDERS     │
├──────────────────┤     ├──────────────────┤     ├──────────────┤
│ inventory.php    │     │  sales.php       │     │ purchase.php │
│ inventory_s.php  │     │                  │     │              │
│                  │     │ ┌──────────────┐ │     │ ┌──────────┐ │
│ • View Products  │     │ │ Create Bill  │ │     │ │ Add Order│ │
│ • Add Product    │     │ │ Add Items    │ │     │ │ Supplier │ │
│ • Edit Product   │◄────┤ │ Calculate    │─┼────►│ │ Items    │ │
│ • Delete Product │     │ │ Print Bill   │ │     │ │ Update   │ │
│ • Stock Alerts   │     │ └──────────────┘ │     │ │ Stock    │ │
│ • Image Upload   │     │                  │     │ └──────────┘ │
└────────┬─────────┘     └──────────────────┘     └──────────────┘
         │
         │ Uses                    ┌──────────────────┐
         ▼                         │    ANALYTICS     │
┌──────────────────┐               ├──────────────────┤
│  LOGIC ACTIONS   │               │ analytics.php    │
├──────────────────┤               │                  │
│ add.php          │               │ • Sales Reports  │
│ update_stock.php │               │ • Revenue Chart  │
│ delete.php       │               │ • Top Products   │
└──────────────────┘               │ • Stock Trends   │
                                   │ • Low Stock Warn │
                                   └──────────────────┘

┌──────────────────┐     ┌──────────────────┐     ┌──────────────┐
│  SALES HISTORY   │     │ PURCHASE HISTORY │     │   REFUNDS    │
├──────────────────┤     ├──────────────────┤     ├──────────────┤
│sales_history.php │     │purchase_history  │     │ refunds.php  │
│                  │     │      .php        │     │              │
│ • View Bills     │     │                  │     │ • View List  │
│ • Search         │     │ • View Orders    │     │ • Request    │
│ • Date Filter    │     │ • Search         │     │ • Approve    │
│ • Bill Details   │     │ • Date Filter    │     │ • Reject     │
│ • Print Invoice  │     │ • Order Details  │     │ • Process    │
└──────────────────┘     └──────────────────┘     └──────┬───────┘
                                                          │
                                                          │ Uses
                                                          ▼
                                               ┌────────────────────┐
                                               │ REFUND LOGIC       │
                                               ├────────────────────┤
                                               │ refund_request.php │
                                               │ refund_action.php  │
                                               │ connection/        │
                                               │   refunds.php      │
                                               └────────────────────┘
```

## 4. Database Relationships (Entity-Relationship Diagram)

```
┌─────────────┐
│   users     │
├─────────────┤
│ id (PK)     │
│ username    │
│ password    │
│ role        │
└─────────────┘

┌──────────────────┐            ┌─────────────────┐
│    products      │            │     bills       │
├──────────────────┤            ├─────────────────┤
│ id (PK)          │            │ id (PK)         │
│ name             │◄───────┐   │ customer_name   │
│ category         │        │   │ total_amount    │
│ quantity         │        │   │ bill_date       │
│ price            │        │   └────────┬────────┘
│ reorder_level    │        │            │
│ image            │        │            │
└─────────┬────────┘        │            │ 1
          │                 │            │
          │ 1               │            │
          │                 │            │ has many
          │                 │            │
          │ used in         │ references │
          │                 │            ▼ N
          │            ┌────┴─────────────────────┐
          │            │     bill_items           │
          │            ├──────────────────────────┤
          │            │ id (PK)                  │
          └───────────►│ bill_id (FK) ───────────┤
                       │ product_id (FK)          │
                       │ product_name             │
                       │ quantity                 │
                       │ price                    │
                       └──────────────────────────┘
                                    ▲
                                    │ links to
                                    │
                         ┌──────────┴──────────┐
                         │     refunds         │
                         ├─────────────────────┤
                         │ id (PK)             │
                         │ bill_id (FK)        │
                         │ amount              │
                         │ reason              │
                         │ status              │
                         │ request_date        │
                         └─────────────────────┘

┌────────────────────┐            ┌──────────────────┐
│  supply_orders     │            │  supply_items    │
├────────────────────┤            ├──────────────────┤
│ id (PK)            │───────┐    │ id (PK)          │
│ supplier_name      │       │    │ supply_order_id  │
│ total_amount       │ 1     │    │   (FK)           │
│ order_date         │       │    │ product_id (FK)  │
└────────────────────┘       │    │ quantity         │
                             └───►│ price            │
                              has └──────────────────┘
                              many      │ references
                                        │
                              ┌─────────┘
                              │
                              ▼
                        ┌──────────────┐
                        │   products   │
                        └──────────────┘
```

## 5. Data Flow Diagram

```
                    ┌──────────────────────────┐
                    │         USER             │
                    └────────────┬─────────────┘
                                 │
                    ┌────────────┴────────────┐
                    │  HTTP Request           │
                    │  (GET/POST)             │
                    └────────────┬────────────┘
                                 │
                                 ▼
                    ┌─────────────────────────┐
                    │  PHP Frontend Pages     │
                    │  • Dashboard            │
                    │  • Features             │
                    └────────────┬────────────┘
                                 │
            ┌────────────────────┼────────────────────┐
            │                    │                    │
            ▼                    ▼                    ▼
    ┌──────────────┐    ┌──────────────┐    ┌──────────────┐
    │ Read Request │    │Write Request │    │Auth Request  │
    │              │    │              │    │              │
    │ • Display    │    │ • Add        │    │ • Login      │
    │ • List       │    │ • Update     │    │ • Logout     │
    │ • Search     │    │ • Delete     │    │ • Session    │
    └──────┬───────┘    └──────┬───────┘    └──────┬───────┘
           │                   │                    │
           │                   │                    │
           └───────────┬───────┴────────────────────┘
                       │
                       ▼
            ┌──────────────────────┐
            │  Business Logic      │
            │  (logics/)           │
            │                      │
            │  • Validation        │
            │  • Processing        │
            │  • Calculation       │
            └──────────┬───────────┘
                       │
                       ▼
            ┌──────────────────────┐
            │  Database Connection │
            │  (db.php - PDO)      │
            │                      │
            │  • Prepared Stmts    │
            │  • Error Handling    │
            └──────────┬───────────┘
                       │
                       ▼
            ┌──────────────────────┐
            │  MySQL Database      │
            │  (ims)               │
            │                      │
            │  • Execute Query     │
            │  • Return Results    │
            └──────────┬───────────┘
                       │
                       │ Response
                       ▼
            ┌──────────────────────┐
            │  PHP Processing      │
            │  • Format Data       │
            │  • Generate HTML     │
            └──────────┬───────────┘
                       │
                       ▼
            ┌──────────────────────┐
            │  HTTP Response       │
            │  (HTML + CSS + JS)   │
            └──────────┬───────────┘
                       │
                       ▼
                  ┌─────────┐
                  │  USER   │
                  │ (View)  │
                  └─────────┘
```

## 6. Security Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    SECURITY LAYERS                          │
└─────────────────────────────────────────────────────────────┘

Layer 1: Authentication
┌──────────────────────────────────────┐
│  • Session Management (PHP)          │
│  • Login Validation (auth.php)       │
│  • Password Storage (hashed)         │
│  • Role-Based Access (admin/staff)   │
└──────────────────────────────────────┘
                  │
                  ▼
Layer 2: Authorization
┌──────────────────────────────────────┐
│  • Page-level Access Control         │
│  • Role Verification                 │
│  • Feature Restrictions              │
│    - Admin: Full Access              │
│    - Staff: Limited Access           │
└──────────────────────────────────────┘
                  │
                  ▼
Layer 3: Database Security
┌──────────────────────────────────────┐
│  • PDO Prepared Statements           │
│  • SQL Injection Prevention          │
│  • Input Validation                  │
│  • Error Handling & Logging          │
└──────────────────────────────────────┘
                  │
                  ▼
Layer 4: Session Security
┌──────────────────────────────────────┐
│  • Session Start on Auth             │
│  • Session Destroy on Logout         │
│  • Session Validation on Each Page   │
│  • Auto-redirect if Not Logged In    │
└──────────────────────────────────────┘
```

## 7. Technology Stack

```
┌─────────────────────────────────────────────────────────┐
│                  TECHNOLOGY STACK                       │
└─────────────────────────────────────────────────────────┘

Frontend Layer
├── HTML5
├── CSS3 (css/style.css)
└── JavaScript (Embedded in PHP)

Backend Layer
├── PHP 8+ (Server-side scripting)
├── PDO (Database abstraction)
└── Session Management

Database Layer
├── MySQL/MariaDB (RDBMS)
└── phpMyAdmin (Management tool)

Server Environment
├── XAMPP Stack
│   ├── Apache HTTP Server
│   ├── MySQL Database
│   └── PHP Runtime
└── macOS (Development environment)

Additional Components
├── AI Chat Widget (AI Assistant)
└── File Upload System (Product images)
```

## 8. Deployment Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                 LOCAL DEVELOPMENT SETUP                     │
└─────────────────────────────────────────────────────────────┘

                      ┌──────────────┐
                      │   Browser    │
                      │  (Client)    │
                      └──────┬───────┘
                             │
                             │ HTTP Request
                             │ localhost/inventory-management-system
                             │
                      ┌──────▼────────────────────────┐
                      │    Apache HTTP Server         │
                      │    (Port 80/443)              │
                      │                               │
                      │  Document Root:               │
                      │  /xamppfiles/htdocs/          │
                      │    inventory-management-      │
                      │    system/                    │
                      └──────┬────────────────────────┘
                             │
                             │ PHP Processing
                             │
                      ┌──────▼────────────────────────┐
                      │    PHP Runtime                │
                      │    (Version 8+)               │
                      │                               │
                      │  • Execute PHP Scripts        │
                      │  • Session Handling           │
                      │  • File Operations            │
                      └──────┬────────────────────────┘
                             │
                             │ Database Queries (PDO)
                             │
                      ┌──────▼────────────────────────┐
                      │   MySQL/MariaDB Server        │
                      │   (Port 3306)                 │
                      │                               │
                      │   Database: ims               │
                      │   • Tables                    │
                      │   • Data Storage              │
                      └───────────────────────────────┘

Management Tool:
┌─────────────────────────────────────┐
│       phpMyAdmin                    │
│  (localhost/phpmyadmin)             │
│                                     │
│  • Database Management              │
│  • SQL Query Interface              │
│  • Import/Export                    │
└─────────────────────────────────────┘
```

---

## Diagram Legend

- **PK**: Primary Key
- **FK**: Foreign Key
- **1**: One relationship
- **N**: Many relationship
- **◄─**: Data flow direction
- **─►**: References/Uses
- **▼**: Process flow

---

## Notes

1. **Role-Based Access**: The system implements role-based access control with two roles:
   - Admin (manager): Full system access
   - Staff: Limited access (view-only inventory, sales features only)

2. **Session Management**: PHP sessions are used throughout for authentication and authorization

3. **Database Connection**: PDO (PHP Data Objects) is used for secure database interactions with prepared statements

4. **Modular Architecture**: Features are organized into separate modules for maintainability

5. **AI Integration**: The system includes an AI chat widget for user assistance

6. **Refund Workflow**: Requests can be made by staff and must be approved by admin

---

_Generated: February 4, 2026_
_System: Inventory Management System (IMS)_
_Version: 1.0_
