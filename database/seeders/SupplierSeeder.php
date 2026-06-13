<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['name' => 'Apple Inc', 'code' => 'SUP001', 'contact' => 'Tim Cook', 'phone' => '+1-408-996-1010', 'email' => 'orders@apple.com', 'address' => '1 Apple Park Way, Cupertino, CA 95014'],
            ['name' => 'Samsung Electronics', 'code' => 'SUP002', 'contact' => 'Han Jong-hee', 'phone' => '+82-2-2255-0114', 'email' => 'b2b@samsung.com', 'address' => '129 Samsung-ro, Yeongtong-gu, Suwon, Korea'],
            ['name' => 'Intel Corporation', 'code' => 'SUP003', 'contact' => 'Pat Gelsinger', 'phone' => '+1-408-765-8080', 'email' => 'enterprise@intel.com', 'address' => '2200 Mission College Blvd, Santa Clara, CA 95054'],
            ['name' => 'NVIDIA Corporation', 'code' => 'SUP004', 'contact' => 'Jensen Huang', 'phone' => '+1-408-486-2000', 'email' => 'sales@nvidia.com', 'address' => '2788 San Thomas Expressway, Santa Clara, CA 95051'],
            ['name' => 'AMD Inc', 'code' => 'SUP005', 'contact' => 'Lisa Su', 'phone' => '+1-408-749-4000', 'email' => 'orders@amd.com', 'address' => '2485 Augustine Drive, Santa Clara, CA 95054'],
            ['name' => 'Dell Technologies', 'code' => 'SUP006', 'contact' => 'Michael Dell', 'phone' => '+1-800-456-3355', 'email' => 'enterprise@dell.com', 'address' => '1 Dell Way, Round Rock, TX 78682'],
            ['name' => 'HP Inc', 'code' => 'SUP007', 'contact' => 'Enrique Lores', 'phone' => '+1-650-857-1501', 'email' => 'business@hp.com', 'address' => '1501 Page Mill Road, Palo Alto, CA 94304'],
            ['name' => 'Lenovo Group', 'code' => 'SUP008', 'contact' => 'Yang Yuanqing', 'phone' => '+86-10-5886-8000', 'email' => 'enterprise@lenovo.com', 'address' => '6 Chuangye Road, Haidian, Beijing, China'],
            ['name' => 'ASUSTeK Computer', 'code' => 'SUP009', 'contact' => 'TH Tung', 'phone' => '+886-2-2894-3447', 'email' => 'sales@asus.com', 'address' => '15 Li-Te Rd, Taipei, Taiwan'],
            ['name' => 'Micron Technology', 'code' => 'SUP010', 'contact' => 'Sanjay Mehrotra', 'phone' => '+1-208-368-4000', 'email' => 'orders@micron.com', 'address' => '8000 S Federal Way, Boise, ID 83707'],
            ['name' => 'Western Digital', 'code' => 'SUP011', 'contact' => 'David Goeckeler', 'phone' => '+1-408-717-6000', 'email' => 'enterprise@wd.com', 'address' => '5601 Great Oaks Parkway, San Jose, CA 95119'],
            ['name' => 'Seagate Technology', 'code' => 'SUP012', 'contact' => 'Dave Mosley', 'phone' => '+1-831-439-1000', 'email' => 'orders@seagate.com', 'address' => '47488 Kato Road, Fremont, CA 94538'],
            ['name' => 'SK Hynix', 'code' => 'SUP013', 'contact' => 'Lee Seok-hee', 'phone' => '+82-31-8093-3700', 'email' => 'sales@skhynix.com', 'address' => '2091 Gyungchun-ro, Paju-si, Gyeonggi-do, Korea'],
            ['name' => 'Qualcomm Inc', 'code' => 'SUP014', 'contact' => 'Cristiano Amon', 'phone' => '+1-858-587-1121', 'email' => 'sales@qti.qualcomm.com', 'address' => '5775 Morehouse Drive, San Diego, CA 92121'],
            ['name' => 'MediaTek Inc', 'code' => 'SUP015', 'contact' => 'Rick Tsai', 'phone' => '+886-2-2216-7899', 'email' => 'orders@mediatek.com', 'address' => '1 Dusing Rd, Hsinchu, Taiwan'],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create([
                'name' => $supplier['name'],
                'code' => $supplier['code'],
                'contact_person' => $supplier['contact'],
                'phone' => $supplier['phone'],
                'email' => $supplier['email'],
                'address' => $supplier['address'],
                'status' => 1,
            ]);
        }
    }
}
