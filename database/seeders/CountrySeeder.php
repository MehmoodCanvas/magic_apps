<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            ['name' => 'Afghanistan', 'code' => 'AF'],
            ['name' => 'Albania', 'code' => 'AL'],
            ['name' => 'Algeria', 'code' => 'DZ'],
            ['name' => 'Andorra', 'code' => 'AD'],
            ['name' => 'Angola', 'code' => 'AO'],
            ['name' => 'Argentina', 'code' => 'AR'],
            ['name' => 'Armenia', 'code' => 'AM'],
            ['name' => 'Australia', 'code' => 'AU'],
            ['name' => 'Austria', 'code' => 'AT'],
            ['name' => 'Azerbaijan', 'code' => 'AZ'],
            ['name' => 'Bahamas', 'code' => 'BS'],
            ['name' => 'Bahrain', 'code' => 'BH'],
            ['name' => 'Bangladesh', 'code' => 'BD'],
            ['name' => 'Belarus', 'code' => 'BY'],
            ['name' => 'Belgium', 'code' => 'BE'],
            ['name' => 'Belize', 'code' => 'BZ'],
            ['name' => 'Benin', 'code' => 'BJ'],
            ['name' => 'Bhutan', 'code' => 'BT'],
            ['name' => 'Bolivia', 'code' => 'BO'],
            ['name' => 'Bosnia and Herzegovina', 'code' => 'BA'],
            ['name' => 'Botswana', 'code' => 'BW'],
            ['name' => 'Brazil', 'code' => 'BR'],
            ['name' => 'Brunei', 'code' => 'BN'],
            ['name' => 'Bulgaria', 'code' => 'BG'],
            ['name' => 'Burkina Faso', 'code' => 'BF'],
            ['name' => 'Burundi', 'code' => 'BI'],
            ['name' => 'Cambodia', 'code' => 'KH'],
            ['name' => 'Cameroon', 'code' => 'CM'],
            ['name' => 'Canada', 'code' => 'CA'],
            ['name' => 'Cape Verde', 'code' => 'CV'],
            ['name' => 'Central African Republic', 'code' => 'CF'],
            ['name' => 'Chad', 'code' => 'TD'],
            ['name' => 'Chile', 'code' => 'CL'],
            ['name' => 'China', 'code' => 'CN'],
            ['name' => 'Colombia', 'code' => 'CO'],
            ['name' => 'Comoros', 'code' => 'KM'],
            ['name' => 'Congo', 'code' => 'CG'],
            ['name' => 'Costa Rica', 'code' => 'CR'],
            ['name' => 'Croatia', 'code' => 'HR'],
            ['name' => 'Cuba', 'code' => 'CU'],
            ['name' => 'Cyprus', 'code' => 'CY'],
            ['name' => 'Czech Republic', 'code' => 'CZ'],
            ['name' => 'Denmark', 'code' => 'DK'],
            ['name' => 'Djibouti', 'code' => 'DJ'],
            ['name' => 'Dominica', 'code' => 'DM'],
            ['name' => 'Dominican Republic', 'code' => 'DO'],
            ['name' => 'Ecuador', 'code' => 'EC'],
            ['name' => 'Egypt', 'code' => 'EG'],
            ['name' => 'El Salvador', 'code' => 'SV'],
            ['name' => 'Equatorial Guinea', 'code' => 'GQ'],
            ['name' => 'Eritrea', 'code' => 'ER'],
            ['name' => 'Estonia', 'code' => 'EE'],
            ['name' => 'Eswatini', 'code' => 'SZ'],
            ['name' => 'Ethiopia', 'code' => 'ET'],
            ['name' => 'Fiji', 'code' => 'FJ'],
            ['name' => 'Finland', 'code' => 'FI'],
            ['name' => 'France', 'code' => 'FR'],
            ['name' => 'Gabon', 'code' => 'GA'],
            ['name' => 'Gambia', 'code' => 'GM'],
            ['name' => 'Georgia', 'code' => 'GE'],
            ['name' => 'Germany', 'code' => 'DE'],
            ['name' => 'Ghana', 'code' => 'GH'],
            ['name' => 'Greece', 'code' => 'GR'],
            ['name' => 'Guatemala', 'code' => 'GT'],
            ['name' => 'Guinea', 'code' => 'GN'],
            ['name' => 'Guyana', 'code' => 'GY'],
            ['name' => 'Haiti', 'code' => 'HT'],
            ['name' => 'Honduras', 'code' => 'HN'],
            ['name' => 'Hungary', 'code' => 'HU'],
            ['name' => 'Iceland', 'code' => 'IS'],
            ['name' => 'India', 'code' => 'IN'],
            ['name' => 'Indonesia', 'code' => 'ID'],
            ['name' => 'Iran', 'code' => 'IR'],
            ['name' => 'Iraq', 'code' => 'IQ'],
            ['name' => 'Ireland', 'code' => 'IE'],
            ['name' => 'Israel', 'code' => 'IL'],
            ['name' => 'Italy', 'code' => 'IT'],
            ['name' => 'Jamaica', 'code' => 'JM'],
            ['name' => 'Japan', 'code' => 'JP'],
            ['name' => 'Jordan', 'code' => 'JO'],
            ['name' => 'Kazakhstan', 'code' => 'KZ'],
            ['name' => 'Kenya', 'code' => 'KE'],
            ['name' => 'Kuwait', 'code' => 'KW'],
            ['name' => 'Kyrgyzstan', 'code' => 'KG'],
            ['name' => 'Laos', 'code' => 'LA'],
            ['name' => 'Latvia', 'code' => 'LV'],
            ['name' => 'Lebanon', 'code' => 'LB'],
            ['name' => 'Lesotho', 'code' => 'LS'],
            ['name' => 'Liberia', 'code' => 'LR'],
            ['name' => 'Libya', 'code' => 'LY'],
            ['name' => 'Lithuania', 'code' => 'LT'],
            ['name' => 'Luxembourg', 'code' => 'LU'],
            ['name' => 'Madagascar', 'code' => 'MG'],
            ['name' => 'Malawi', 'code' => 'MW'],
            ['name' => 'Malaysia', 'code' => 'MY'],
            ['name' => 'Maldives', 'code' => 'MV'],
            ['name' => 'Mali', 'code' => 'ML'],
            ['name' => 'Malta', 'code' => 'MT'],
            ['name' => 'Mauritania', 'code' => 'MR'],
            ['name' => 'Mauritius', 'code' => 'MU'],
            ['name' => 'Mexico', 'code' => 'MX'],
            ['name' => 'Moldova', 'code' => 'MD'],
            ['name' => 'Monaco', 'code' => 'MC'],
            ['name' => 'Mongolia', 'code' => 'MN'],
            ['name' => 'Montenegro', 'code' => 'ME'],
            ['name' => 'Morocco', 'code' => 'MA'],
            ['name' => 'Mozambique', 'code' => 'MZ'],
            ['name' => 'Myanmar', 'code' => 'MM'],
            ['name' => 'Namibia', 'code' => 'NA'],
            ['name' => 'Nepal', 'code' => 'NP'],
            ['name' => 'Netherlands', 'code' => 'NL'],
            ['name' => 'New Zealand', 'code' => 'NZ'],
            ['name' => 'Nicaragua', 'code' => 'NI'],
            ['name' => 'Niger', 'code' => 'NE'],
            ['name' => 'Nigeria', 'code' => 'NG'],
            ['name' => 'North Korea', 'code' => 'KP'],
            ['name' => 'North Macedonia', 'code' => 'MK'],
            ['name' => 'Norway', 'code' => 'NO'],
            ['name' => 'Oman', 'code' => 'OM'],
            ['name' => 'Pakistan', 'code' => 'PK'],
            ['name' => 'Palestine', 'code' => 'PS'],
            ['name' => 'Panama', 'code' => 'PA'],
            ['name' => 'Papua New Guinea', 'code' => 'PG'],
            ['name' => 'Paraguay', 'code' => 'PY'],
            ['name' => 'Peru', 'code' => 'PE'],
            ['name' => 'Philippines', 'code' => 'PH'],
            ['name' => 'Poland', 'code' => 'PL'],
            ['name' => 'Portugal', 'code' => 'PT'],
            ['name' => 'Qatar', 'code' => 'QA'],
            ['name' => 'Romania', 'code' => 'RO'],
            ['name' => 'Russia', 'code' => 'RU'],
            ['name' => 'Rwanda', 'code' => 'RW'],
            ['name' => 'Saudi Arabia', 'code' => 'SA'],
            ['name' => 'Senegal', 'code' => 'SN'],
            ['name' => 'Serbia', 'code' => 'RS'],
            ['name' => 'Seychelles', 'code' => 'SC'],
            ['name' => 'Sierra Leone', 'code' => 'SL'],
            ['name' => 'Singapore', 'code' => 'SG'],
            ['name' => 'Slovakia', 'code' => 'SK'],
            ['name' => 'Slovenia', 'code' => 'SI'],
            ['name' => 'Somalia', 'code' => 'SO'],
            ['name' => 'South Africa', 'code' => 'ZA'],
            ['name' => 'South Korea', 'code' => 'KR'],
            ['name' => 'South Sudan', 'code' => 'SS'],
            ['name' => 'Spain', 'code' => 'ES'],
            ['name' => 'Sri Lanka', 'code' => 'LK'],
            ['name' => 'Sudan', 'code' => 'SD'],
            ['name' => 'Suriname', 'code' => 'SR'],
            ['name' => 'Sweden', 'code' => 'SE'],
            ['name' => 'Switzerland', 'code' => 'CH'],
            ['name' => 'Syria', 'code' => 'SY'],
            ['name' => 'Taiwan', 'code' => 'TW'],
            ['name' => 'Tajikistan', 'code' => 'TJ'],
            ['name' => 'Tanzania', 'code' => 'TZ'],
            ['name' => 'Thailand', 'code' => 'TH'],
            ['name' => 'Togo', 'code' => 'TG'],
            ['name' => 'Trinidad and Tobago', 'code' => 'TT'],
            ['name' => 'Tunisia', 'code' => 'TN'],
            ['name' => 'Turkey', 'code' => 'TR'],
            ['name' => 'Turkmenistan', 'code' => 'TM'],
            ['name' => 'Uganda', 'code' => 'UG'],
            ['name' => 'Ukraine', 'code' => 'UA'],
            ['name' => 'United Arab Emirates', 'code' => 'AE'],
            ['name' => 'United Kingdom', 'code' => 'GB'],
            ['name' => 'United States', 'code' => 'US'],
            ['name' => 'Uruguay', 'code' => 'UY'],
            ['name' => 'Uzbekistan', 'code' => 'UZ'],
            ['name' => 'Venezuela', 'code' => 'VE'],
            ['name' => 'Vietnam', 'code' => 'VN'],
            ['name' => 'Yemen', 'code' => 'YE'],
            ['name' => 'Zambia', 'code' => 'ZM'],
            ['name' => 'Zimbabwe', 'code' => 'ZW'],
        ];

        DB::table('countries')->insert($countries);

        // insert categories data
        $categories = [
            ['name' => 'Technology & IT', 'slug' => 'technology-it'],
            ['name' => 'Business & Finance', 'slug' => 'business-finance'],
            ['name' => 'Creative Arts & Design', 'slug' => 'creative-arts-design'],
            ['name' => 'Sales & Customer', 'slug' => 'sales-customer'],
            ['name' => 'Healthcare & Medical', 'slug' => 'healthcare-medical'],
            ['name' => 'Engineering', 'slug' => 'engineering'],
            ['name' => 'Legal & Compliance', 'slug' => 'legal-compliance'],
            ['name' => 'Education & Training', 'slug' => 'education-training'],
            ['name' => 'Human Resources', 'slug' => 'human-resources'],
            ['name' => 'Hospitality & Travel', 'slug' => 'hospitality-travel'],
            ['name' => 'Science & Research', 'slug' => 'science-research'],
            ['name' => 'Media & Journalism', 'slug' => 'media-journalism'],
            ['name' => 'Real Estate', 'slug' => 'real-estate'],
            ['name' => 'Marketing & Advertising', 'slug' => 'marketing-advertising'],
        ];

        DB::table('categories')->insert($categories);

        // insert sub_categories data
        $techCategoryId = 1; // "Technology & IT" category ID
        $subCategories = [
            ['name' => 'Software Development', 'slug' => 'software-development', 'category_id' => $techCategoryId],
            ['name' => 'Web Design', 'slug' => 'web-design', 'category_id' => $techCategoryId],
            ['name' => 'App Development', 'slug' => 'app-development', 'category_id' => $techCategoryId],
            ['name' => 'Data Science & Analytics', 'slug' => 'data-science-analytics', 'category_id' => $techCategoryId],
            ['name' => 'Cybersecurity', 'slug' => 'cybersecurity', 'category_id' => $techCategoryId],
            ['name' => 'Cloud Computing', 'slug' => 'cloud-computing', 'category_id' => $techCategoryId],
            ['name' => 'Artificial Intelligence & Machine Learning', 'slug' => 'artificial-intelligence-machine-learning', 'category_id' => $techCategoryId],
            ['name' => 'IT Support & Infrastructure', 'slug' => 'it-support-infrastructure', 'category_id' => $techCategoryId],
        ];

        DB::table('sub_categories')->insert($subCategories);

        // insert qualifications data
        $qualifications = [
            ['name' => 'Certificates', 'status' => 1],
            ['name' => 'Diplomas', 'status' => 1],
            ['name' => "Bachelor's Degrees", 'status' => 1],
            ['name' => "Master's Degrees", 'status' => 1],
            ['name' => 'Doctoral Degrees (PhDs)', 'status' => 1],
        ];

        DB::table('qualifications')->insert($qualifications);

        // insert work_styles data
        $work_styles = [
            ['name' => 'Logical', 'status' => 1],
            ['name' => 'Idea-oriented', 'status' => 1],
            ['name' => "Supportive", 'status' => 1],
            ['name' => "Cooperative", 'status' => 1],
            ['name' => 'Independent/Logical', 'status' => 1],
            ['name' => 'Proximity', 'status' => 1],
        ];

        DB::table('work_styles')->insert($work_styles);

        // insert employment_statuses data
        $employment_statuses = [
            ['name' => 'Employee', 'status' => 1],
            ['name' => 'Worker', 'status' => 1],
            ['name' => "Self-employed", 'status' => 1],
        ];

        DB::table('employment_statuses')->insert($employment_statuses);
    }
}
