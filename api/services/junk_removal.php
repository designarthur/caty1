<?php
// api/services/junk_removal.php

// Define system prompt for Junk Removal
$system_prompt = <<<PROMPT
You are a helpful assistant for {$companyName} focused on Junk Removal services.
Your process is a strict two-step process. Do not combine the steps.

**STEP 1: ITEM IDENTIFICATION & CONFIRMATION**
- If the user uploads an image or provides a description, your FIRST task is to analyze it and list all identifiable items.
- For EACH item identified, You MUST use this exact format:
🟥 Item: [Item Name]
📏 Size: [Your best estimate of the dimensions, e.g., "3x2x2 ft"]
⚖️ Weight: [Your best estimate of the weight, e.g., "approx. 50 lbs"]

- After listing ALL items, your response MUST end with this exact question: **"Is this list correct, or do you need to make any changes?"**
- **IMPORTANT: DO NOT ask for the customer's name, email, phone, or address in this step.**

**STEP 2: GATHER CUSTOMER INFO & SUBMIT**
- **IF the user confirms the list is correct** (e.g., they say "yes", "correct", "that's it", "looks good"), you MUST immediately proceed to ask for the required customer information:
    - Full Name
    - Email Address
    - Phone Number
    - Service Location (full address)
    - Preferred Date
    - Preferred Time
- **IF the user wants to make changes** (e.g., "add a fridge", "remove the chair"), you must update the list, present the complete, updated list again, and go back to the end of STEP 1 to ask for confirmation.
- Once you have the final, confirmed item list AND all the customer information, summarize everything for a final review before using the 'submit_quote_request' tool.
PROMPT;

// Define tools for Junk Removal
$tools = [
    [
        'type' => 'function',
        'function' => [
            'name' => 'submit_quote_request',
            'description' => 'Submits the collected information to create a junk removal quote request.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'service_type' => ['type' => 'string', 'enum' => ['junk_removal'], 'description' => 'The type of service requested. Must be "junk_removal".'],
                    'customer_type' => ['type' => 'string', 'enum' => ['Residential', 'Commercial'], 'description' => 'The type of customer (Residential or Commercial).'],
                    'customer_name' => ['type' => 'string', 'description' => 'Full name of the customer.'],
                    'customer_email' => ['type' => 'string', 'description' => 'Email address of the customer.'],
                    'customer_phone' => ['type' => 'string', 'description' => 'Phone number of the customer.'],
                    'location' => ['type' => 'string', 'description' => 'Full address or detailed location for the service.'],
                    'service_date' => ['type' => 'string', 'description' => 'The preferred date for the service in YYYY-MM-DD format.'],
                    'service_time' => ['type' => 'string', 'description' => 'The preferred time for the service (e.g., "morning", "afternoon", "10:00 AM").'],
                    'is_urgent' => ['type' => 'boolean', 'description' => 'True if the request is urgent, false otherwise.'],
                    'driver_instructions' => ['type' => 'string', 'description' => 'Any specific instructions for the driver.'],
                    'junk_details' => [
                        'type' => 'object',
                        'description' => 'Details for a junk removal request, including inferred items from description or uploaded media.',
                         'properties' => [
                            'junk_items' => [
                                'type' => 'array',
                                'description' => 'List of junk items, inferred from description or uploaded media.',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'itemType' => ['type' => 'string', 'description' => 'Type of junk item, e.g., "Sofa", "Refrigerator", "Construction Debris".'],
                                        'quantity' => ['type' => 'integer', 'description' => 'Estimated quantity of the item.'],
                                        'estDimensions' => ['type' => 'string', 'description' => 'Estimated dimensions, e.g., "8x3x3 ft", "Large".'],
                                        'estWeight' => ['type' => 'string', 'description' => 'Estimated weight, e.g., "100kg", "Heavy".']
                                    ],
                                    'required' => ['itemType', 'quantity']
                                ]
                            ],
                            'recommended_dumpster_size' => ['type' => 'string', 'description' => 'Recommended dumpster size if applicable, e.g., "20-yard".'],
                            'additional_comment' => ['type' => 'string', 'description' => 'Any additional comments or specific requests regarding the removal.'],
                            'media_urls' => [
                                'type' => 'array',
                                'description' => 'URLs of uploaded images or video frames for junk removal, if provided by the user.',
                                'items' => ['type' => 'string']
                            ]
                        ],
                        'required' => ['junk_items']
                    ]
                ],
                'required' => ['service_type', 'customer_type', 'customer_name', 'customer_email', 'customer_phone', 'location', 'service_date', 'service_time', 'junk_details']
            ]
        ]
    ]
];
?>