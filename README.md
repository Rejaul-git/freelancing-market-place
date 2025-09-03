# Freelance Marketplace Platform

A modern and easy-to-use freelancing marketplace platform where clients can post jobs and hire freelancers, and freelancers can showcase their skills and earn.

**Demo:** [https://marketplace.brainstone.xyz/](https://marketplace.brainstone.xyz/)

## login

admin: admin@gmail.com
pass: 12345

buyer: buyer@gmail.com
pass: 12345
seller:seller@gmail.com
pass:12345

---

## Tech Stack

- **Backend:** PHP
- **Frontend:** React
- **Database:** MySQL
- **Web Server:** Apache

---

## Installation (Local Setup)

1. Clone the repository:

   ```bash
   git clone https://github.com/your-username/your-repo.git
   cd your-repo
   ```

2. Copy the `.env` file and configure environment variables:

   ```bash
   cp .env.example .env
   ```

3. Install dependencies via Composer:

   ```bash
   composer install
   ```

4. Generate the application key:

   ```bash
   php artisan key:generate
   ```

5. Run database migrations and seeders:

   ```bash
   php artisan migrate --seed
   ```

6. Start the local development server:

   ```bash
   php artisan serve
   ```

7. Open your browser at:

   ```
   http://127.0.0.1:8000
   ```

---

## Usage

- **Clients:**

  - Create an account
  - Post jobs (gigs) with detailed requirements
  - Browse freelancers and hire for projects

- **Freelancers:**
  - Create a professional profile
  - Browse and bid on gigs
  - Complete projects and receive ratings

---

## Features

- User Registration & Authentication (Clients & Freelancers)
- Gig Posting & Management System
- Proposal (Bidding) Module
- Payment Gateway Integration (if enabled)
- Review & Rating System
- Dashboard for managing profile, jobs, and proposals

---

## Roadmap / Future Improvements

- In-app messaging system
- Job progress tracking tools
- Multi-language interface
- Mobile app integration (Android / iOS)

---

## Contributing

- Report bugs or request features via Issues.
- Submit Pull Requests for approved features or bug fixes.
- Follow PSR standards and Laravel best practices.
- Discuss major changes before starting work on them.

---

## Contact

- Email: rejaulk431@gmail.com
- Website: [https://marketplace.brainstone.xyz/](https://marketplace.brainstone.xyz/)

---

## License

This project currently has no assigned license.  
For open-source use, consider MIT, Apache 2.0, or another appropriate license.
