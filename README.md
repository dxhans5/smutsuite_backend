# 🌶️ SmutSuite

**The consent-first API platform for intimacy-based creators, hosts, and service providers.**  
Built for safety, freedom, and full-spectrum control.

---

## 🫶 For Creators, Hosts, and Service Providers

SmutSuite isn't just another social app — it's your personal command center for professional intimacy work.

### ✅ Core Features (Live & Ready)

**🎭 Multi-Identity System**
- Single account, multiple personas (Creator, Host, Service Provider, Admin)
- Each identity has separate profiles, wallets, and reputation tracking
- Seamless identity switching with full audit logging
- Verification status per identity with flexible visibility controls

**💬 Real-Time Communication**
- WebSocket-powered messaging with thread participants
- Read receipts and participant tracking
- Live message broadcasting across all connected clients
- Identity-scoped conversations with privacy controls

**📅 Professional Scheduling**
- Dynamic availability rules with day/time granularity
- Real-time booking requests with status transitions
- Live availability broadcasting to potential clients
- Timezone-aware scheduling with conflict prevention

**🔒 Enterprise-Grade Security**
- Sanctum authentication with hashed refresh tokens (30-day expiry)
- Role-based access control with granular permissions
- Policy-driven authorization for all sensitive operations
- Complete audit trails for identity switches and booking changes

**👤 Dual Profile Architecture**
- Public profiles for discovery and marketing
- Private profiles for CRM, notes, and emotional tracking
- Identity-scoped profile management with owner-only access
- Geo-visibility controls and local hiding options

**🔔 Smart Notifications**
- Generic notification system with database persistence
- Real-time push notifications for booking requests
- Event-driven alerts for availability changes
- Customizable notification preferences per identity

**📡 Live Event Broadcasting**
- Availability updates broadcast to discovery channels
- Booking request notifications to relevant parties
- Message delivery with real-time thread updates
- Status changes propagated across all connected clients

🔒 **Consent-first by design** — you're always in control.

---

## 💸 For Investors and Partners

**SmutSuite is not a clone.** It's what platforms like OnlyFans, Calendly, and Stripe *should have been* if they were designed for real-world intimacy-based work.

### What Makes It Different

🧠 **Sophisticated Identity Architecture** — One user, multiple professional personas with separate everything  
📡 **Real-Time Infrastructure** — WebSocket broadcasting for availability, bookings, and messaging  
🏗️ **API-First Design** — Built to power mobile + web from a single, robust Laravel backend  
📍 **Location-Aware Discovery** — Granular geo-controls with local hiding and verified-only visibility  
🗃️ **Professional CRM** — Session journaling, mood tracking, client notes, and consent logging  
🧾 **Enterprise Admin Tools** — Comprehensive audit logs, permission management, and moderation infrastructure  
🔄 **Real-Time Everything** — Live booking requests, availability updates, and message delivery  
🧩 **Beyond Content** — Built for bookings, events, scheduling, and professional service delivery

### Current Platform Capabilities

**Authentication & Identity Management**
- Multi-factor authentication with email verification
- Hashed refresh token system with automatic rotation
- Identity switching with complete audit trails
- Role-based permissions with policy enforcement

**Real-Time Communication Infrastructure**
- WebSocket message broadcasting via Laravel Reverb
- Thread-based messaging with participant management
- Read receipt tracking and online status indicators
- Identity-scoped conversation privacy

**Professional Booking System**
- Dynamic availability rule management
- Real-time booking request broadcasting
- Status transition workflows (pending → confirmed → completed)
- Calendar integration with timezone handling

**Discovery & Visibility Controls**
- Public profile management with visibility toggles
- Geo-based discovery filtering and local hiding
- Verification status integration with search algorithms
- Real-time availability broadcasting to discovery channels

**Administrative Infrastructure**
- Comprehensive role and permission management
- Identity verification workflows
- Audit logging for all sensitive operations
- Policy-driven access control across all endpoints

---

## 🛠 Technical Architecture

| Layer         | Technology                 | Implementation Status |
|---------------|----------------------------|----------------------|
| Backend       | Laravel 12 (PHP 8.2.20)   | ✅ Production Ready  |
| Database      | PostgreSQL + Redis         | ✅ Fully Configured  |
| Real-Time     | WebSockets (Laravel Reverb)| ✅ Live Broadcasting |
| Auth          | Sanctum + Refresh Tokens   | ✅ Enterprise Secure |
| Messaging     | WebSocket + Thread System  | ✅ Real-Time DMs     |
| Broadcasting  | Event-Driven Updates       | ✅ Live Everything   |
| API Design    | JsonResource + Envelopes   | ✅ Consistent Format |
| Permissions   | RBAC + Policy Authorization| ✅ Granular Control  |
| CI/CD         | GitHub Actions             | 🚧 In Development   |

📱 **Frontend**: *In progress — API fully ready for PWA and Flutter implementation.*

---

## ✅ Production-Ready Features

### 🔐 Authentication & Security
- [x] Sanctum-based API authentication with token management
- [x] Hashed refresh tokens with 30-day expiry and rotation
- [x] Email verification workflow with resend capabilities
- [x] Age verification (21+) with birth date validation
- [x] Google OAuth integration endpoints (ready for implementation)

### 🎭 Multi-Identity System
- [x] User account with multiple operational identities
- [x] Identity types: User, Creator, Host, Service Provider, Content Provider
- [x] Active identity switching with audit logging
- [x] Verification status per identity (pending, verified, rejected)
- [x] Identity-scoped visibility controls (public, members, hidden)

### 👤 Profile Management
- [x] Dual profile architecture (public + private per identity)
- [x] Public profiles for discovery and marketing
- [x] Private profiles for CRM, notes, and mood tracking
- [x] Geo-visibility controls and local hiding
- [x] Identity-scoped profile access with policy enforcement

### 💬 Real-Time Messaging
- [x] Thread-based messaging system with participant tracking
- [x] WebSocket broadcasting for live message delivery
- [x] Read receipt tracking with timestamp management
- [x] Identity-scoped conversations with privacy controls
- [x] Message soft-deletion with sender-only permissions

### 📅 Professional Scheduling
- [x] Dynamic availability rules with day/time granularity
- [x] Real-time booking request system with status workflows
- [x] Live availability broadcasting to discovery channels
- [x] Booking status transitions with event broadcasting
- [x] Timezone-aware scheduling with conflict detection

### 🔔 Notification Infrastructure
- [x] Generic notification system with database persistence
- [x] Real-time notification broadcasting
- [x] Notification preferences and read/unread tracking
- [x] Event-driven notification triggers

### 🔧 Administrative Features
- [x] Role-based access control with granular permissions
- [x] Policy-driven authorization across all endpoints
- [x] User and permission management APIs
- [x] Identity verification workflows
- [x] Comprehensive audit logging for sensitive operations

### 📡 Real-Time Broadcasting
- [x] Availability update broadcasting
- [x] Booking request and status change events
- [x] Live message delivery across threads
- [x] Discovery channel updates for online status

### 🌍 Internationalization
- [x] Full localization support with `__()` helper integration
- [x] Localized error messages and user feedback
- [x] Multi-language ready codebase structure

---

## 🚧 Strategic Roadmap

### Phase 1: Frontend Integration (Q1 2025)
- [ ] 📱 Flutter mobile app development
- [ ] 🌐 PWA implementation with offline support
- [ ] 🎨 Creator dashboard with analytics
- [ ] 📊 Admin panel with moderation tools

### Phase 2: Monetization Infrastructure (Q2 2025)
- [ ] 💳 Stripe Connect integration for multi-party payments
- [ ] 💰 Creator tips and fan subscription system
- [ ] 📈 Revenue analytics and tax reporting
- [ ] 🔗 Public booking links with QR code generation

### Phase 3: Advanced Safety & Discovery (Q3 2025)
- [ ] 📍 Advanced geo-blocking and smart visibility controls
- [ ] 🤖 AI-based mood and CRM suggestion engine
- [ ] 🛡️ Enhanced safety tools and blocklist sharing
- [ ] 🔍 Advanced search and discovery algorithms

### Phase 4: Platform Expansion (Q4 2025)
- [ ] 🖇️ Co-branded partner pages and white-label solutions
- [ ] 🗃️ Advanced session archiving and tagging system
- [ ] 🎭 Optional decentralized identity integration
- [ ] 🌐 Multi-language platform expansion

---

## 🔧 Development Standards

### Architecture Principles
- **API-Only Design**: Pure JSON API with no Blade views
- **UUID Everywhere**: No auto-incrementing IDs across the entire platform
- **Resource Pattern**: All responses via JsonResource with `{"data": ...}` envelope
- **Event-Driven**: Real-time broadcasting for all major user actions
- **Policy-Based Security**: Authorization handled through Laravel policies
- **Multi-Identity First**: Every feature designed around identity-scoped access

### Code Quality Standards
- **Laravel 12.21.0** with **PHP 8.2.20** compatibility
- **Comprehensive test coverage** with feature and unit tests
- **Localization ready** with `__('key')` for all user-facing text
- **Type-safe enums** for status fields and business logic
- **Middleware-based security** for all sensitive operations
- **Database-driven configuration** with environment-specific settings

### Development Workflow
- **Test-First Development**: All features built with comprehensive test coverage
- **Real-Time Integration**: WebSocket broadcasting tested in all major workflows
- **Identity-Scoped Testing**: All tests validate multi-identity behavior
- **Policy Validation**: Authorization policies tested across all user types
- **Event Broadcasting**: Real-time features validated with WebSocket test clients

---

## 🎯 Platform Differentiators

🧠 **True Multi-Identity Architecture** — Not just user accounts with roles, but completely separate professional personas with independent reputation, finances, and visibility

📡 **Real-Time Everything** — Built from the ground up for live interaction, not retrofitted with basic notifications

🔒 **Consent-First Design Philosophy** — Every feature designed around explicit consent, boundary management, and user control

🏗️ **Professional Infrastructure** — Enterprise-grade authentication, audit logging, and administrative controls built for serious business use

🌍 **Global-Ready Platform** — Multi-language, multi-timezone, multi-currency architecture from day one

🔧 **API-First Architecture** — Mobile-native design that can power any frontend implementation

---

> **This isn't just an app. It's infrastructure for safety, consent, and real-world sex-positive work.**

Let's build something better.

—

**🧠 SmutSuite Core Team**
