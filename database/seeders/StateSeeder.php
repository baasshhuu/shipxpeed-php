<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $states = [
            [
                "id"            => "1",
                "name"          => "Andaman & Nicobar Islands",
                "status"        => "1"
            ],
            [
                "id"            => "2",
                "name"          => "Andhra Pradesh",
                "status"        => "1"
            ],
            [
                "id"            => "3",
                "name"          => "Arunachal Pradesh",
                "status"        => "1"
            ],
            [
                "id"            => "4",
                "name"          => "Assam",
                "status"        => "1"
            ],
            [
                "id"            => "5",
                "name"          => "Bihar",
                "status"        => "1"
            ],
            [
                "id"            => "6",
                "name"          => "Chandigarh",
                "status"        => "1"
            ],
            [
                "id"            => "7",
                "name"          => "Chhattisgarh",
                "status"        => "1"
            ],
            [
                "id"            => "8",
                "name"          => "Dadra & Nagar Haveli",
                "status"        => "1"
            ],
            [
                "id"            => "9",
                "name"          => "Daman & Diu",
                "status"        => "1"
            ],
            [
                "id"            => "10",
                "name"          => "Delhi",
                "status"        => "1"
            ],
            [
                "id"            => "11",
                "name"          => "Goa",
                "status"        => "1"
            ],
            [
                "id"            => "12",
                "name"          => "Gujarat",
                "status"        => "1"
            ],
            [
                "id"            => "13",
                "name"          => "Haryana",
                "status"        => "1"
            ],
            [
                "id"            => "14",
                "name"          => "Himachal Pradesh",
                "status"        => "1"
            ],
            [
                "id"            => "15",
                "name"          => "Jammu & Kashmir",
                "status"        => "1"
            ],
            [
                "id"            => "16",
                "name"          => "Jharkhand",
                "status"        => "1"
            ],
            [
                "id"            => "17",
                "name"          => "Karnataka",
                "status"        => "1"
            ],
            [
                "id"            => "18",
                "name"          => "Kerala",
                "status"        => "1"
            ],
            [
                "id"            => "19",
                "name"          => "Lakshadweep",
                "status"        => "1"
            ],
            [
                "id"            => "20",
                "name"          => "Madhya Pradesh",
                "status"        => "1"
            ],
            [
                "id"            => "21",
                "name"          => "Maharashtra",
                "status"        => "1"
            ],
            [
                "id"            => "22",
                "name"          => "Manipur",
                "status"        => "1"
            ],
            [
                "id"            => "23",
                "name"          => "Meghalaya",
                "status"        => "1"
            ],
            [
                "id"            => "24",
                "name"          => "Mizoram",
                "status"        => "1"
            ],
            [
                "id"            => "25",
                "name"          => "Nagaland",
                "status"        => "1"
            ],
            [
                "id"            => "26",
                "name"          => "Odisha",
                "status"        => "1"
            ],
            [
                "id"            => "27",
                "name"          => "Puducherry",
                "status"        => "1"
            ],
            [
                "id"            => "28",
                "name"          => "Punjab",
                "status"        => "1"
            ],
            [
                "id"            => "29",
                "name"          => "Rajasthan",
                "status"        => "1"
            ],
            [
                "id"            => "30",
                "name"          => "Sikkim",
                "status"        => "1"
            ],
            [
                "id"            => "31",
                "name"          => "Tamil Nadu",
                "status"        => "1"
            ],
            [
                "id"            => "32",
                "name"          => "Tripura",
                "status"        => "1"
            ],
            [
                "id"            => "33",
                "name"          => "Uttar Pradesh",
                "status"        => "1"
            ],
            [
                "id"            => "34",
                "name"          => "Uttarakhand",
                "status"        => "1"
            ],
            [
                "id"            => "35",
                "name"          => "West Bengal",
                "status"        => "1"
            ]
        ];

        State::insert($states);
    }
}
