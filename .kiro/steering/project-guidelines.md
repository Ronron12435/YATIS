# YATIS - Yet Another Total Information System

## Overview

YATIS is a multi-feature social and business platform built on Laravel 12. It combines social networking, business management, job posting, tourism discovery, restaurant operations, and event gamification into a single system.

## User Roles

- `user` - Standard user with social and consumer features
- `business` - Business owner managing a business profile
- `employer` - Posts job listings and manages applications
- `admin` - Full system access including admin dashboard

## Features by Domain

### User & Social Features
- User registration and authentication (Sanctum token-based)
- Profile management (bio, profile picture, cover photo, location)
- Social posts (create, edit, delete, view by user)
- Friend system (send/accept/reject/block requests)
- Private messaging (direct messages, unread counts, mark as read)
- Group creation and management (public/private, member limits)
- Group messaging

### Business Features
- Business registration (food, goods, services types)
- Business hours and open/closed status
- Menu item management (for food businesses)
- Product management (for goods businesses)
- Service management (for service businesses)
- Restaurant table generation and reservation
- Business subscription status

### Job Portal Features
- Job posting (title, description, requirements, salary range, location, job type)
- Job status management (open/closed toggle)
- Job applications with resume and cover letter upload
- Application status tracking (pending, reviewed, accepted, rejected)
- Interview date scheduling
- Employer dashboard (pending application counts, my jobs)

### Tourism Features
- Tourist destination listings with location and images
- Destination reviews and ratings (1–5 stars)
- Average rating calculation

### Events & Gamification Features
- Event creation and management (admin only)
- Event tasks (steps, location, qr_scan, custom types)
- Task completion with proof data
- Points and achievements system
- Leaderboard

### Search Features
- Global search across users, businesses, destinations, posts
- Advanced search with filters

### Admin Features
- System statistics dashboard
- Business management
- Event management

## Core Models

| Model | Purpose |
|---|---|
| User | Central entity, all roles |
| Business | Business profiles |
| MenuItem | Restaurant menu items |
| Product | Business products |
| Service | Business services |
| RestaurantTable | Table reservations |
| JobPosting | Job listings |
| JobApplication | Job applications |
| Post | Social posts |
| Friendship | Friend connections |
| PrivateMessage | Direct messages |
| Group | User groups |
| GroupMessage | Group chat messages |
| TouristDestination | Tourist attractions |
| DestinationReview | Destination reviews |
| Event | Events with tasks |
| EventTask | Individual event tasks |
| UserTaskCompletion | Task completion records |
| UserAchievement | User achievement badges |
