<?php
/**
 * Category-Specific Filters Configuration - Enhanced based on Jiji Blueprint
 */

function get_category_filters($cat_name) {
    $cat_name = strtoupper($cat_name);

    $filters = [];

    // Vehicles / Cars
    if (strpos($cat_name, 'VEHICLES') !== false || strpos($cat_name, 'CARS') !== false) {
        $filters = [
            'price' => [
                'label' => 'Price Range (₦)',
                'type' => 'range',
                'quick_ranges' => [
                    ['label' => 'Under ₦5.3M', 'min' => 0, 'max' => 5300000],
                    ['label' => '5.3M–13M', 'min' => 5300000, 'max' => 13000000],
                    ['label' => '13M–28M', 'min' => 13000000, 'max' => 28000000],
                    ['label' => '28M–92M', 'min' => 28000000, 'max' => 92000000],
                    ['label' => '92M+', 'min' => 92000000, 'max' => 99999999999]
                ]
            ],
            'make' => [
                'label' => 'Make (Brand)',
                'type' => 'select',
                'searchable' => true,
                'options' => [
                    'Toyota', 'Mercedes-Benz', 'Lexus', 'Honda', 'Hyundai', 'Acura', 'Audi', 'BMW', 'BYD', 'Bentley',
                    'Cadillac', 'Changan', 'Chevrolet', 'Chrysler', 'Dodge', 'Ford', 'GAC', 'Geely', 'GMC', 'Infiniti',
                    'Isuzu', 'IVM', 'JAC', 'Jaguar', 'Jeep', 'Jetour', 'Kia', 'Lamborghini', 'Land Rover', 'Lincoln',
                    'Maserati', 'Mazda', 'Mini', 'Mitsubishi', 'Nissan', 'Opel', 'Peugeot', 'Pontiac', 'Porsche',
                    'Rolls-Royce', 'Subaru', 'Tesla', 'Volkswagen', 'Volvo', 'XPeng', 'Abarth', 'Alfa Romeo', 'Alpine',
                    'Aston Martin', 'Baic', 'Baojun', 'Beijing', 'Borgward', 'Brilliance', 'Bristol', 'Bugatti', 'Buick',
                    'Caterham', 'Chery', 'Citroen', 'Cupra', 'Dacia', 'Dadi', 'Daewoo', 'Daihatsu', 'DFSK', 'Dongfeng',
                    'Donkervoort', 'DS', 'Eicher', 'Ferrari', 'Fiat', 'Fisker', 'Force', 'Foton', 'Genesis', 'Gonow',
                    'Great Wall', 'Gumpert', 'Hafei', 'Haima', 'Haval', 'Higer', 'Holden', 'Huanghai', 'Hummer', 'Ineos',
                    'Invicta', 'JMC', 'Karma', 'KTM', 'Lada', 'Lancia', 'Landwind', 'Lifan', 'Lotus', 'Luxgen', 'Lynk & Co',
                    'Mahindra', 'Marussia', 'Maxus', 'Maybach', 'McLaren', 'MG', 'Microcar', 'Morgan', 'Nio', 'Noble',
                    'Oldsmobile', 'Pagani', 'Perodua', 'Polestar', 'Proton', 'Qvale', 'Radical', 'Ram', 'Ravon', 'Renault',
                    'Rimac', 'Rivian', 'Roewe', 'Ruf', 'Saab', 'Saleen', 'Scion', 'Seat', 'Shelby', 'Skoda', 'Smart',
                    'Spyker', 'SsangYong', 'Suzuki', 'Tata', 'Tvr', 'Ultima', 'Vauxhall', 'Venturi', 'Wiesmann', 'Zotye'
                ]
            ],
            'year' => [
                'label' => 'Year of Manufacture',
                'type' => 'number_range',
                'quick_ranges' => [
                    ['label' => '2022-2026', 'min' => 2022, 'max' => 2026],
                    ['label' => '2017-2021', 'min' => 2017, 'max' => 2021],
                    ['label' => '2012-2016', 'min' => 2012, 'max' => 2016],
                    ['label' => '2007-2011', 'min' => 2007, 'max' => 2011],
                    ['label' => '2002-2006', 'min' => 2002, 'max' => 2006],
                    ['label' => '1997-2001', 'min' => 1997, 'max' => 2001],
                    ['label' => '1992-1996', 'min' => 1992, 'max' => 1996],
                    ['label' => '1987 and older', 'min' => 0, 'max' => 1987]
                ]
            ],
            'condition' => [
                'label' => 'Condition',
                'type' => 'select',
                'options' => ['Brand New', 'Foreign Used', 'Local Used']
            ],
            'transmission' => [
                'label' => 'Transmission',
                'type' => 'select',
                'options' => ['AMT', 'Automatic', 'CVT', 'Manual']
            ],
            'mileage' => [
                'label' => 'Mileage (km)',
                'type' => 'number_range'
            ],
            'registered' => [
                'label' => 'Registered Car',
                'type' => 'select',
                'options' => ['Yes', 'No']
            ],
            'body_type' => [
                'label' => 'Body',
                'type' => 'multi_select',
                'searchable' => true,
                'options' => ['Wagon', 'Van', 'Truck', 'SUV', 'Station Wagon', 'Sedan', 'Pickup', 'Panel Van', 'Minivan', 'Hatchback', 'Crossover', 'Coupe', 'Convertible Coupe', 'Convertible']
            ],
            'color' => [
                'label' => 'Color',
                'type' => 'select',
                'options' => ['Black', 'Blue', 'Gray', 'Silver', 'White', 'Beige', 'Brown', 'Burgundy', 'Gold', 'Green', 'Ivory', 'Maroon', 'Matt Black', 'Off white', 'Orange', 'Pearl', 'Pink', 'Purple', 'Red', 'Teal', 'Yellow', 'Other']
            ],
            'engine_size' => [
                'label' => 'Engine Size (cc)',
                'type' => 'select',
                'options' => [
                    '660cc', '700cc', '800cc', '900cc', '1000cc', '1100cc', '1200cc', '1300cc', '1400cc', '1500cc', '1600cc', '1700cc',
                    '1800cc', '1900cc', '2000cc', '2100cc', '2200cc', '2300cc', '2400cc', '2500cc', '2600cc', '2700cc', '2800cc',
                    '2900cc', '3000cc', '3100cc', '3200cc', '3300cc', '3400cc', '3500cc', '3600cc', '3700cc', '3800cc', '3900cc',
                    '4000cc', '4100cc', '4200cc', '4300cc', '4400cc', '4500cc', '4600cc', '4700cc', '4800cc', '4900cc', '5000cc',
                    '5200cc', '5300cc', '5400cc', '5500cc', '5600cc', '5700cc', '5800cc', '5900cc', '6000cc', '6200cc',
                    '6500cc', '6700cc', '7000cc', '7300cc', '7500cc', '8000cc', '8100cc'
                ]
            ],
            'powertrain' => [
                'label' => 'Powertrain',
                'type' => 'select',
                'options' => ['Internal Combustion', 'Hybrid', 'Electric', 'Fuel Plug-in Hybrid']
            ],
            'fuel_type' => [
                'label' => 'Fuel Type',
                'type' => 'select',
                'options' => ['Petrol', 'Diesel', 'Hybrid', 'Mild Hybrid', 'Electric', 'CNG', 'Plug-in Hybrid']
            ],
            'exchange' => [
                'label' => 'Exchange offers available',
                'type' => 'checkbox'
            ],
            'second_condition' => [
                'label' => 'Second Condition',
                'type' => 'select',
                'options' => ['No faults', 'Original parts', 'Unpainted', 'First owner', 'First registration', 'After crash', 'Engine issue', 'Gear issue', 'Need body repair', 'Need repainting', 'Need repair', 'Wiring problems']
            ]
        ];
    }
    // Electronics
    elseif (strpos($cat_name, 'ELECTRONICS') !== false || strpos($cat_name, 'LAPTOPS') !== false || strpos($cat_name, 'TV') !== false || strpos($cat_name, 'COMPUTERS') !== false || strpos($cat_name, 'PHONES') !== false) {
        $filters = [
            'price' => [
                'label' => 'Price Range (₦)',
                'type' => 'range'
            ],
            'condition' => [
                'label' => 'Condition',
                'type' => 'select',
                'options' => ['Brand New', 'Foreign Used', 'Local Used']
            ],
            'brand' => [
                'label' => 'Brand',
                'type' => 'select',
                'searchable' => true,
                'options' => ['Dell', 'HP', 'Lenovo', 'Apple', 'Sony', 'LG', 'Samsung', 'Acer', 'Asus', 'Toshiba', 'Panasonic', 'Hisense', 'TCL', 'Huawei', 'Xiaomi', 'Tecno', 'Infinix', 'Itel', 'Oppo', 'Vivo', 'Realme', 'Microsoft', 'Google', 'Nintendo', 'Sega', 'Canon', 'Nikon', 'Fujifilm']
            ],
            'type' => [
                'label' => 'Type',
                'type' => 'select',
                'options' => [
                    'Laptops', 'Desktops', 'Tablets', 'Monitors', 'Televisions', 'Smart Watches', 'Audio Equipment', 'Cameras',
                    'Game Consoles', 'Video Games', 'Computer Hardware', 'Computer Accessories', 'Software', 'Networking Products',
                    'Printers & Scanners', 'Projectors', 'Security & Surveillance', 'Storage Devices'
                ]
            ]
        ];

        if (strpos($cat_name, 'TV') !== false || strpos($cat_name, 'MONITOR') !== false) {
            $filters['screen_size'] = [
                'label' => 'Screen Size (inches)',
                'type' => 'number_range'
            ];
        }

        if (strpos($cat_name, 'LAPTOP') !== false || strpos($cat_name, 'COMPUTER') !== false) {
            $filters['storage'] = [
                'label' => 'Storage',
                'type' => 'select',
                'options' => ['128GB SSD', '256GB SSD', '512GB SSD', '1TB SSD', '2TB SSD', '500GB HDD', '1TB HDD', '2TB HDD', '64GB', '128GB', '256GB']
            ];
            $filters['ram'] = [
                'label' => 'RAM (GB)',
                'type' => 'select',
                'options' => ['2GB', '4GB', '8GB', '12', '16GB', '24GB', '32GB', '64GB']
            ];
        }
    }
    // Property
    elseif (strpos($cat_name, 'PROPERTY') !== false || strpos($cat_name, 'HOUSES') !== false || strpos($cat_name, 'LAND') !== false) {
        $filters = [
            'price' => [
                'label' => 'Price Range (₦)',
                'type' => 'range',
                'quick_ranges' => [
                    ['label' => 'Under ₦250K', 'min' => 0, 'max' => 250000],
                    ['label' => '250K-10M', 'min' => 250000, 'max' => 10000000],
                    ['label' => '10M-200M', 'min' => 10000000, 'max' => 200000000],
                    ['label' => '200M-1.4B', 'min' => 200000000, 'max' => 1400000000],
                    ['label' => '1.4B+', 'min' => 1400000000, 'max' => 99999999999]
                ]
            ],
            'verified_seller' => [
                'label' => 'Verified Sellers',
                'type' => 'select',
                'search_only' => true,
                'options' => ['Verified sellers only', 'All sellers']
            ],
            'discount' => [
                'label' => 'Discount',
                'type' => 'select',
                'search_only' => true,
                'options' => ['With discount', 'Without discount']
            ],
            'transaction_type' => [
                'label' => 'Transaction Type',
                'type' => 'select',
                'options' => ['For Sale (Outright)', 'For Rent (per annum)', 'For Rent (per month)', 'For Rent (per day)']
            ],
            'property_type' => [
                'label' => 'Property Type',
                'type' => 'select',
                'options' => ['Land', 'Residential', 'Commercial', 'Industrial']
            ],
            'size' => [
                'label' => 'Size (sqm)',
                'type' => 'number_range'
            ],
            'bedrooms' => [
                'label' => 'Bedrooms',
                'type' => 'select',
                'options' => ['1 bedroom', '2 bedrooms', '3 bedrooms', '4+ bedrooms']
            ],
            'trusted_agent' => [
                'label' => 'Trusted Real Estate Agent',
                'type' => 'select',
                'search_only' => true,
                'options' => ['Yes', 'No']
            ]
        ];
    }

    return $filters;
}
