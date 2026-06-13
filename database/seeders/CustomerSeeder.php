<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['name' => 'TechCorp Solutions', 'code' => 'CUST001', 'contact' => 'John Smith', 'phone' => '+1-555-0101', 'email' => 'john.smith@techcorp.com', 'address' => '123 Tech Street, Silicon Valley, CA 94025', 'level' => 'VIP'],
            ['name' => 'Global Electronics Ltd', 'code' => 'CUST002', 'contact' => 'Sarah Johnson', 'phone' => '+1-555-0102', 'email' => 'sarah.j@globalelec.com', 'address' => '456 Commerce Ave, New York, NY 10001', 'level' => 'Gold'],
            ['name' => 'InnovateTech Inc', 'code' => 'CUST003', 'contact' => 'Michael Chen', 'phone' => '+1-555-0103', 'email' => 'm.chen@innovatetech.io', 'address' => '789 Innovation Blvd, Austin, TX 78701', 'level' => 'Gold'],
            ['name' => 'Digital Dynamics', 'code' => 'CUST004', 'contact' => 'Emily Davis', 'phone' => '+44-20-1234-5678', 'email' => 'emily.d@digitaldynamics.co.uk', 'address' => '10 Downing Street, London, SW1A 2AA', 'level' => 'Silver'],
            ['name' => 'Smart Solutions GmbH', 'code' => 'CUST005', 'contact' => 'Hans Mueller', 'phone' => '+49-30-1234567', 'email' => 'h.mueller@smartsolutions.de', 'address' => 'Friedrichstr. 123, 10117 Berlin, Germany', 'level' => 'Silver'],
            ['name' => 'Pacific Trading Co', 'code' => 'CUST006', 'contact' => 'Lisa Wang', 'phone' => '+86-21-1234-5678', 'email' => 'lisa@pacifictrading.cn', 'address' => '888 Nanjing Road, Shanghai 200001', 'level' => 'Normal'],
            ['name' => 'Nordic Electronics', 'code' => 'CUST007', 'contact' => 'Erik Larsson', 'phone' => '+46-8-123 456', 'email' => 'erik@nordicelec.se', 'address' => 'Kungsgatan 42, 111 43 Stockholm', 'level' => 'Gold'],
            ['name' => 'Mediterranean Tech', 'code' => 'CUST008', 'contact' => 'Maria Rossi', 'phone' => '+39-02-1234567', 'email' => 'm.rossi@medtech.it', 'address' => 'Via Roma 100, 20121 Milan, Italy', 'level' => 'Silver'],
            ['name' => 'Aussie Tech Solutions', 'code' => 'CUST009', 'contact' => 'James Wilson', 'phone' => '+61-2-9876-5432', 'email' => 'james@aussietech.au', 'address' => '45 George Street, Sydney NSW 2000', 'level' => 'Normal'],
            ['name' => 'Sunrise Electronics', 'code' => 'CUST010', 'contact' => 'Yuki Tanaka', 'phone' => '+81-3-1234-5678', 'email' => 'y.tanaka@sunrise-elec.jp', 'address' => '1-1-1 Shibuya, Tokyo 150-0002', 'level' => 'VIP'],
            ['name' => 'Brazil Tech Imports', 'code' => 'CUST011', 'contact' => 'Carlos Silva', 'phone' => '+55-11-1234-5678', 'email' => 'carlos@braziltech.br', 'address' => 'Av. Paulista 1000, Sao Paulo 01310-100', 'level' => 'Silver'],
            ['name' => 'Nordic Systems AB', 'code' => 'CUST012', 'contact' => 'Anna Lindberg', 'phone' => '+47-22-84-1234', 'email' => 'anna@nordicsystems.no', 'address' => 'Karl Johans gate 15, 0159 Oslo', 'level' => 'Gold'],
            ['name' => 'Phoenix Technologies', 'code' => 'CUST013', 'contact' => 'David Brown', 'phone' => '+1-602-555-0100', 'email' => 'd.brown@phoenixtech.com', 'address' => '500 E Camelback Rd, Phoenix, AZ 85012', 'level' => 'Normal'],
            ['name' => 'Delta Computing', 'code' => 'CUST014', 'contact' => 'Jennifer Lee', 'phone' => '+1-206-555-0100', 'email' => 'j.lee@deltabytes.com', 'address' => '1200 6th Ave, Seattle, WA 98101', 'level' => 'Silver'],
            ['name' => 'Quantum Labs Inc', 'code' => 'CUST015', 'contact' => 'Robert Kim', 'phone' => '+1-650-555-0100', 'email' => 'r.kim@quantumlabs.ai', 'address' => '3500 Deer Creek Rd, Palo Alto, CA 94304', 'level' => 'VIP'],
        ];

        foreach ($customers as $customer) {
            Customer::create([
                'name' => $customer['name'],
                'code' => $customer['code'],
                'contact_person' => $customer['contact'],
                'phone' => $customer['phone'],
                'email' => $customer['email'],
                'address' => $customer['address'],
                'level' => $customer['level'],
                'status' => 1,
            ]);
        }
    }
}
