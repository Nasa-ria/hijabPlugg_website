# Hijabis Plugg - E-commerce Platform

A comprehensive e-commerce platform built with Laravel, featuring a modern storefront and powerful admin panel for managing products, orders, and customer reviews.

## Features

### Customer-Facing Features
- ✅ Browse products with images, descriptions, and pricing
- ✅ Product detail pages with full descriptions
- ✅ Star ratings and written reviews for products
- ✅ Shopping cart functionality
- ✅ Secure checkout process
- ✅ Order tracking and history
- ✅ User registration and authentication
- ✅ Guest browsing (registration required for checkout)

### Admin Panel Features
- ✅ Product management (add, edit, delete products)
- ✅ Category management
- ✅ Order management and status updates
- ✅ Review moderation and replies
- ✅ Dashboard with analytics
- ✅ Image upload and storage
- ✅ Stock management

### Technical Features
- ✅ Built with Laravel 11 (latest stable)
- ✅ Responsive design with Tailwind CSS
- ✅ Paystack payment integration
- ✅ Role-based authentication
- ✅ RESTful API structure
- ✅ Image storage with Laravel filesystem
- ✅ Database relationships and migrations

## Installation & Setup

### Prerequisites
- PHP 8.1 or higher
- Composer
- Node.js & NPM
- MySQL or PostgreSQL database
- Paystack account (for payments)

### Step 1: Install Dependencies
```bash
composer install
npm install
```

### Step 2: Environment Configuration
Copy the environment file and configure your settings:
```bash
cp .env.example .env
```

Update your `.env` file with the following configurations:
```env
APP_NAME="Hijabis Plugg"
APP_ENV=local
APP_KEY=base64:your-app-key-here
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hijabis_plugg
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password

# Paystack Payment Gateway
PAYSTACK_PUBLIC_KEY=pk_test_your_public_key
PAYSTACK_SECRET_KEY=sk_test_your_secret_key
PAYMENT_CURRENCY=GHS

# Mail Configuration (optional)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@hijabisplugg.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Step 3: Generate Application Key
```bash
php artisan key:generate
```

### Step 4: Run Database Migrations
```bash
php artisan migrate
```

### Step 5: Seed Database (Optional)
```bash
php artisan db:seed
```

### Step 6: Create Storage Link
```bash
php artisan storage:link
```

### Step 7: Build Assets
```bash
npm run build
# OR for development
npm run dev
```

### Step 8: Start Development Server
```bash
php artisan serve
```

## Admin Access

After seeding the database, you can access the admin panel with:
- **Email:** `admin@hijabisplugg.com`
- **Password:** `password`

## Database Schema

The application includes the following main tables:
- `users` - Customer and admin accounts
- `categories` - Product categories
- `products` - Product catalog
- `product_images` - Additional product images
- `reviews` - Customer reviews and ratings
- `orders` - Customer orders
- `order_items` - Individual order line items
- `payments` - Payment records

## Key Routes

### Public Routes
- `/` - Homepage
- `/products` - Product catalog
- `/products/{product}` - Product details
- `/cart` - Shopping cart
- `/checkout` - Checkout process
- `/orders` - Customer order history

### Admin Routes
- `/admin` - Admin dashboard
- `/admin/products` - Product management
- `/admin/categories` - Category management
- `/admin/orders` - Order management
- `/admin/reviews` - Review moderation

## Payment Integration

The application integrates with Paystack for payment processing:
1. Customers initiate payment on the payment page
2. Redirected to Paystack's secure checkout
3. Payment callback updates order status
4. Webhooks handle payment confirmations

## File Structure

```
app/
├── Http/Controllers/
│   ├── Admin/          # Admin controllers
│   ├── CartController.php
│   ├── CheckoutController.php
│   ├── OrderController.php
│   ├── PaymentController.php
│   ├── ProductController.php
│   └── ReviewController.php
├── Models/             # Eloquent models
├── Middleware/         # Custom middleware
database/
├── migrations/         # Database migrations
├── seeders/           # Database seeders
resources/
├── views/             # Blade templates
│   ├── layouts/       # Layout templates
│   ├── admin/         # Admin views
│   ├── cart/          # Cart views
│   ├── checkout/      # Checkout views
│   ├── orders/        # Order views
│   ├── payment/       # Payment views
│   └── products/      # Product views
routes/
├── web.php            # Web routes
```

## Security Features

- CSRF protection on all forms
- Input validation and sanitization
- Role-based access control
- Secure password hashing
- SQL injection prevention
- XSS protection

## Responsive Design

The application is fully responsive and works seamlessly on:
- Desktop computers
- Tablets
- Mobile phones

## Technologies Used

- **Backend:** Laravel 11, PHP 8.1+
- **Frontend:** Blade templates, Tailwind CSS, JavaScript
- **Database:** MySQL/PostgreSQL
- **Payment:** Paystack API
- **Icons:** Font Awesome
- **Styling:** Tailwind CSS

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is licensed under the MIT License.

## Support

For support or questions, please contact the development team or create an issue in the repository.

---

**Built with ❤️ for the Hijabis Plugg community**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
