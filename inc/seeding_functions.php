<?php
/**
 * Jiji-Inspired-1.0 Seeding Functions
 */

function seed_categories($pdo) {
    // Clear existing categories to ensure fresh start
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE categories");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // 1. Seed COMPLETE Jiji-Standard Categories
    $jobs_subs = [
        'Accounting, Auditing & Finance', 'Admin & Office Support', 'Agricultural Jobs', 'Aviation Jobs',
        'Banking Jobs', 'Construction Jobs', 'Customer Service & Call Centre', 'Energy, Oil & Gas Jobs',
        'Engineering & Technical Jobs', 'Healthcare & Nursing', 'Hospitality & Hotel Jobs', 'HR & Recruitment Jobs',
        'ICT & Computer Jobs', 'Legal Jobs', 'Management & Business Development', 'Manufacturing Jobs',
        'Marketing & Communication Jobs', 'Media & Advertisement Jobs', 'NGO, Social & Charity Jobs',
        'Procurement & Logistics Jobs', 'Real Estate Jobs', 'Sales Jobs', 'Transportation & Driving Jobs'
    ];

    $categories_data = [
        'Vehicles' => [
            'icon' => 'fa-car', 'is_top' => 1, 'order' => 1,
            'subs' => ['Cars', 'Motorcycles & Scooters', 'Trucks & Trailers', 'Buses & Microbuses', 'Boats', 'Heavy Equipment', 'Auto Parts & Accessories', 'Car Care & Detailing', 'Number Plates & Stamps']
        ],
        'Property (Real Estate)' => [
            'icon' => 'fa-home', 'is_top' => 1, 'order' => 2,
            'subs' => ['Houses & Apartments for Rent', 'Houses & Apartments for Sale', 'Land & Plots for Sale', 'Land & Plots for Rent / Lease', 'Commercial Property for Rent', 'Commercial Property for Sale', 'Short Let / Daily Rentals', 'New Developments']
        ],
        'Phones & Tablets' => [
            'icon' => 'fa-mobile-alt', 'is_top' => 1, 'order' => 3,
            'subs' => ['Mobile Phones', 'Tablets', 'Accessories for Phones & Tablets']
        ],
        'Electronics' => [
            'icon' => 'fa-tv', 'is_top' => 1, 'order' => 4,
            'subs' => ['Computers & Laptops', 'Computer Accessories & Peripherals', 'TV & DVD Equipment', 'Cameras & Video Cameras', 'Sound & Music Equipment', 'Games & Gaming', 'Printers & Scanners', 'Networking & Connectivity']
        ],
        'Home, Furniture & Appliances' => [
            'icon' => 'fa-couch', 'is_top' => 0, 'order' => 5,
            'subs' => ['Kitchen Appliances', 'Fridges & Freezers', 'Washing Machines', 'Air Conditioning & Fans', 'Sofas & Living Room Sets', 'Beds & Mattresses', 'Dining Sets', 'Office Furniture', 'Wardrobes & Closets', 'Generators, UPS & Solar Energy', 'Lighting & Ceiling Fans', 'Cooking & Baking Appliances', 'Garden & Outdoor Items', 'Curtains & Blinds']
        ],
        'Fashion & Accessories' => [
            'icon' => 'fa-tshirt', 'is_top' => 0, 'order' => 6,
            'subs' => ["Men's Clothing", "Women's Clothing", "Children's Clothing", "Men's Shoes", "Women's Shoes", 'Bags', 'Watches & Accessories', 'Jewelry & Gemstones', 'Sunglasses & Eyewear']
        ],
        'Beauty & Personal Care' => [
            'icon' => 'fa-heartbeat', 'is_top' => 0, 'order' => 7,
            'subs' => ['Skin Care', 'Hair Care & Wigs', 'Make-up & Cosmetics', 'Health Care & Supplements', 'Perfumes & Fragrances', 'Nail Care']
        ],
        'Services' => [
            'icon' => 'fa-concierge-bell', 'is_top' => 0, 'order' => 8,
            'subs' => ['Financial Services', 'Legal Services', 'Education & Training', 'Cleaning & Household Services', 'Car Services & Repair', 'Catering & Chef Services', 'Computer & Technology Services', 'Health Services', 'Moving & Delivery Services', 'Photography & Videography', 'Event Planning & Management', 'Social Media & Digital Marketing']
        ],
        'Repair & Construction' => [
            'icon' => 'fa-tools', 'is_top' => 0, 'order' => 9,
            'subs' => ['Plumbing & Water Systems', 'Electrical Work', 'Construction & Civil Engineering', 'Painting & Decorating', 'Security Systems & Surveillance', 'Tiling & Flooring', 'Roofing', 'AC Repair & Maintenance']
        ],
        'Commercial Equipment & Tools' => [
            'icon' => 'fa-industry', 'is_top' => 0, 'order' => 10,
            'subs' => ['Agricultural Equipment & Tools', 'Construction Equipment', 'Office Equipment & Supplies', 'Industrial Equipment', 'Medical & Lab Equipment', 'Restaurant & Catering Equipment', 'Power Tools']
        ],
        'Leisure & Activities' => [
            'icon' => 'fa-running', 'is_top' => 0, 'order' => 11,
            'subs' => ['Sports Equipment', 'Gym & Fitness Equipment', 'Travel & Tourism Deals', 'Events, Catering & Venues', 'Books, Movies & Music', 'Musical Instruments', 'Arts & Crafts']
        ],
        'Babies & Kids' => [
            'icon' => 'fa-baby', 'is_top' => 0, 'order' => 12,
            'subs' => ['Baby Clothes & Shoes', 'Baby Furniture & Gear', 'Toys & Games', 'Baby Care & Health Products', "Children's Bicycles"]
        ],
        'Food, Agriculture & Farming' => [
            'icon' => 'fa-tractor', 'is_top' => 0, 'order' => 13,
            'subs' => ['Food Products & Groceries', 'Farming Equipment & Tools', 'Livestock & Poultry', 'Crops, Seeds & Fertilizers', 'Farm Lands']
        ],
        'Animals & Pets' => [
            'icon' => 'fa-dog', 'is_top' => 0, 'order' => 14,
            'subs' => ['Dogs', 'Cats', 'Birds', 'Fish & Aquariums', 'Rabbits & Small Animals', 'Pet Accessories & Supplies', 'Pet Food', 'Veterinary Services']
        ],
        'Jobs & Employment' => [
            'icon' => 'fa-briefcase', 'is_top' => 0, 'order' => 15,
            'subs' => $jobs_subs
        ],
        'Seeking Work — CVs' => [
            'icon' => 'fa-id-card', 'is_top' => 0, 'order' => 16,
            'subs' => $jobs_subs
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, icon_class, is_top, sort_order) VALUES (?, ?, ?, ?, ?)");
    $sub_stmt = $pdo->prepare("INSERT INTO categories (name, slug, parent_id) VALUES (?, ?, ?)");

    foreach ($categories_data as $name => $data) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $stmt->execute([$name, $slug, $data['icon'], $data['is_top'], $data['order']]);
        $parent_id = $pdo->lastInsertId();

        foreach ($data['subs'] as $sub_name) {
            $sub_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $sub_name . '-' . $name)));
            $sub_stmt->execute([$sub_name, $sub_slug, $parent_id]);
        }
        // Add "Others" subcategory to every main category
        $other_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', 'Others-' . $name)));
        $sub_stmt->execute(['Others', $other_slug, $parent_id]);
    }
}
