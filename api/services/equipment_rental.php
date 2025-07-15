<?php
// api/services/equipment_rental.php

// Define system prompt for Equipment Rental
$system_prompt = <<<PROMPT
You are a helpful assistant for {$companyName} focused on Junk Removal services.
Your process is a strict three-step process. Do not deviate or combine the steps.

**STEP 1: ITEM IDENTIFICATION & CONFIRMATION**
- If the user provides an image or description, your FIRST task is to analyze it and list all identifiable items.
- For EACH item identified, You MUST use this exact format:
🟥 Item: [Item Name]
📏 Size: [Your best estimate of the dimensions, e.g., "3x2x2 ft"]
⚖️ Weight: [Your best estimate of the weight, e.g., "approx. 50 lbs"]

- After listing ALL items, your response MUST end with this exact question: **"Is this list correct, or do you need to make any changes?"**
- **CRITICAL: DO NOT ask for any customer information (name, address, etc.) in this step.**

**STEP 2: GATHER ALL SERVICE & CUSTOMER DETAILS**
- **ONLY AFTER the user confirms the item list is correct** (e.g., "yes", "correct", "that's it"), you MUST immediately ask for ALL of the following information in a single message:
    - If they are a **Residential** or **Commercial** customer.
    - Their **Full Name**.
    - Their **Email Address**.
    - Their **Phone Number**.
    - The full **Service Location** (address).
    - The **Preferred Date** and **Time** for the service.
    - If the request is **Urgent**.
    - Any specific **Driver Instructions** or **Additional Comments** about the removal.

- **IF the user wants to make changes** to the item list, you must update it, present the complete, updated list again, and go back to the end of STEP 1 to ask for confirmation.

**STEP 3: FINAL SUMMARY & SUBMISSION**
- Once you have the final, confirmed item list AND all the customer and service details from Step 2, you MUST present a complete summary of everything.
- After the summary, ask for final confirmation with this exact question: **"Please review the details above. Is everything correct and are you ready for me to submit this for a quote?"**
- **DO NOT** use the 'submit_quote_request' tool until you receive an explicit "yes" confirmation on this final summary.
PROMPT;

// Define tools for Equipment Rental
$tools = [
    [
        'type' => 'function',
        'function' => [
            'name' => 'submit_quote_request',
            'description' => 'Submits the collected information to create an equipment rental quote request.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'service_type' => ['type' => 'string', 'enum' => ['equipment_rental'], 'description' => 'The type of service requested. Must be "equipment_rental".'],
                    'customer_type' => ['type' => 'string', 'enum' => ['Residential', 'Commercial'], 'description' => 'The type of customer (Residential or Commercial).'],
                    'customer_name' => ['type' => 'string', 'description' => 'Full name of the customer.'],
                    'customer_email' => ['type' => 'string', 'description' => 'Email address of the customer.'],
                    'customer_phone' => ['type' => 'string', 'description' => 'Phone number of the customer.'],
                    'location' => ['type' => 'string', 'description' => 'Full address or detailed location for the service.'],
                    'service_date' => ['type' => 'string', 'description' => 'The preferred date for the service in YYYY-MM-DD format.'],
                    'service_time' => ['type' => 'string', 'description' => 'The preferred time for the service (e.g., "morning", "afternoon", "10:00 AM").'],
                    'is_urgent' => ['type' => 'boolean', 'description' => 'True if the request is urgent, false otherwise.'],
                    'live_load_needed' => ['type' => 'boolean', 'description' => 'True if a live load is needed, false otherwise.'],
                    'driver_instructions' => ['type' => 'string', 'description' => 'Any specific instructions for the driver regarding delivery or placement.'],
                    'equipment_details' => [
                        'type' => 'array',
                        'description' => 'A list of all equipment items for the rental request.',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'equipment_name' => ['type' => 'string', 'description' => 'The name and size of the equipment, e.g., "15-yard dumpster", "temporary toilet".'],
                                'quantity' => ['type' => 'integer', 'description' => 'The number of units required for this specific item.'],
                                'duration_days' => ['type' => 'integer', 'description' => 'The total number of days for the rental period for this item.'],
                                'specific_needs' => ['type' => 'string', 'description' => 'Any other specific requirements or details for this equipment item.']
                            ],
                            'required' => ['equipment_name', 'quantity', 'duration_days']
                        ]
                    ]
                ],
                'required' => ['service_type', 'customer_type', 'customer_name', 'customer_email', 'customer_phone', 'location', 'service_date', 'service_time', 'equipment_details']
            ]
        ]
    ]
];
?>