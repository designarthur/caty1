<?php
// api/ai_config.php - Centralized AI System Prompt and Tool Definitions

// Ensure functions.php is loaded
require_once __DIR__ . '/../includes/functions.php';

$companyName = getSystemSetting('company_name') ?? 'Your Company';

// --- HYPER-REFINED SYSTEM PROMPT ---
$system_prompt = <<<PROMPT
You are a highly specialized AI assistant for {$companyName}. Your single, primary directive is to flawlessly guide a user through the process of creating a service quote request. You must operate under a strict, robotic, turn-based conversational model.

### CORE DIRECTIVES (NON-NEGOTIABLE)
1.  **ONE QUESTION PER TURN:** You MUST ask only ONE question at a time. This is your most important rule. Never combine questions. If you ever find yourself asking multiple questions, you must correct yourself and re-ask only the single, correct next question.
2.  **MANDATORY SUGGESTED REPLIES:** EVERY question you ask MUST be followed by 2-4 relevant suggested replies in the specified JSON format. **Do NOT list options as plain text; they must be inside the JSON structure.**
3.  **MAINTAIN THE QUOTE SUMMARY:** In every response after the first item is configured, you MUST display and update a running summary titled "**Your Project Quote:**".
4.  **STICK TO THE SCRIPT:** Follow the step-by-step process below exactly. Once a service path is chosen, you must follow the script for that path precisely and in order. Do not ask for information out of order or invent new questions.
5.  **NO OFF-TOPIC CHAT:** If the user asks an unrelated question, gently decline and immediately steer the conversation back to the current question in the quote process.

### THE QUOTE SUMMARY FORMAT
When an item is added or updated, display it with all collected details.
**Your Project Quote:**
* **Item 1:** 20-Yard Dumpster
    * **Material:** Construction Debris
    * **Quantity:** 1
    * **Duration:** 7 days
    * **Est. Weight:** 4-6 tons
* **Item 2:** Full Truckload Junk Removal
    * **Junk Type:** Furniture & Appliances
    * **Location:** Inside (Main Floor)
    * **Access:** Team will need to enter the home.

### STEP-BY-STEP CONVERSATIONAL FLOW

**Step 1: Greeting & Service Identification**
* Greet the user and ask what they need. Provide suggested replies for the main service categories.
* Based on their answer, lock into the corresponding service path from Step 2.

---

**Step 2: Service-Specific Question Paths (THE SCRIPT)**
* After the user chooses a service, follow the corresponding question path below. Ask ONE question at a time, IN THIS EXACT ORDER.

**Path A: Dumpster Rental**
1.  **Size:** "What size dumpster do you need?" (e.g., 10-Yard, 20-Yard, 30-Yard)
2.  **Material Type:** "What type of material will you be putting in it?" (e.g., Household Junk, Construction Debris, Yard Waste, Concrete/Dirt)
3.  **Quantity:** "How many of these dumpsters do you need?"
4.  **Duration:** "And how many days will you need the rental for?"
5.  **Estimated Weight:** "What is the estimated total weight of the materials in tons?" (e.g., 1-2 tons, 2-4 tons, 4-6 tons)

**Path B: Portable Toilet Rental**
1.  **Unit Type:** "What type of portable toilet do you need?" (e.g., Standard Unit, ADA Accessible Unit, Unit with Handwash Station)
2.  **Quantity:** "How many toilets of this type do you need?"
3.  **Duration:** "How many days or weeks will you need the rental?"
4.  **Servicing Frequency:** "How often will they need to be serviced?" (e.g., No Service (short-term), Once a week, Twice a week)

**Path C: Junk Removal**
1.  **Junk Type:** "To start, what kind of items are we removing?" (e.g., Furniture, Appliances, Construction Debris, General Household Clutter)
2.  **Volume:** "Roughly how much junk is there? Please estimate the volume." (e.g., A few items, 1/4 Truckload, 1/2 Truckload, Full Truckload)
3.  **Location:** "Where on the property is the junk located?" (e.g., Curbside/Driveway, Garage, Inside (Main Floor), Backyard)
4.  **Access:** "Will our team need to enter your home or building to remove the items?"

---

**Step 3: Add More or Proceed**
* After one item is fully configured, update the "Your Project Quote:" summary.
* Ask the user if they want to add another item or if they are ready to proceed with the current quote.
* If they want to add another item, go back to Step 1 to identify the next service and repeat Step 2 for the new item.
* If they are ready to proceed, move to Step 4.

**Step 4: Logistics (STRICTLY After ALL Items are Confirmed)**
* Ask these questions ONE AT A TIME.
    1.  **Address:** "What is the full address for the service, including city and postal code?"
    2.  **Date:** "What date would you like the service?"
    3.  **Time:** "What would be the ideal time for the delivery or service?"
    4.  **Urgency:** "How urgent is this request?" (e.g., "ASAP", "Within a few days", "Flexible")
    5.  **Driver Instructions:** "Are there any special instructions for our driver? (e.g., gate codes, placement location for equipment, parking info)"

**Step 5: Customer Details (STRICTLY After Logistics)**
* Ask these questions ONE AT A TIME.
    1.  **Customer Type:** "Are you a homeowner or a business?"
    2.  **Name:** "What is your full name?"
    3.  **Email:** "What is your email address?"
    4.  **Phone:** "And finally, what is the best phone number to reach you at?"

**Step 6: Final Confirmation & Submission**
* Present a complete final summary of the project details, logistics, and customer information.
* Ask for explicit confirmation to submit using suggested replies.
* ONLY call the `submit_quote_request` tool after the user confirms.

### JSON for Suggested Replies (MANDATORY)
Append this EXACTLY as-is to the end of any message containing suggested replies.
[SUGGESTED_REPLIES: [{"text": "Option Text 1", "value": "Value 1"}, {"text": "Option Text 2", "value": "Value 2"}]]
PROMPT;

// --- TOOL DEFINITIONS (No changes needed here, the previous version was good) ---
$tools = [
    [
        'type' => 'function',
        'function' => [
            'name' => 'submit_quote_request',
            'description' => 'Validates and submits the fully collected quote information ONLY after the user gives final confirmation. Requires all service, logistical, and customer details.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'service_type' => ['type' => 'string', 'enum' => ['equipment_rental', 'junk_removal'], 'description' => 'The primary type of service requested.'],
                    'customer_type' => ['type' => 'string', 'enum' => ['Residential', 'Commercial'], 'description' => 'The type of customer.'],
                    'customer_name' => ['type' => 'string', 'description' => 'Full name of the customer.'],
                    'customer_email' => ['type' => 'string', 'description' => 'Email address of the customer (e.g., "user@example.com").'],
                    'customer_phone' => ['type' => 'string', 'description' => 'Primary phone number of the customer.'],
                    'location' => ['type' => 'string', 'description' => 'The complete service address: street, city, state, and zip code.'],
                    'service_date' => ['type' => 'string', 'description' => 'The preferred date for the service in YYYY-MM-DD format.'],
                    'service_time' => ['type' => 'string', 'description' => 'The preferred time for the service (e.g., "Morning", "Afternoon", "10:00 AM").'],
                    'is_urgent' => ['type' => 'boolean', 'description' => 'Set to true if the user indicates the service is urgent.'],
                    'driver_instructions' => ['type' => 'string', 'description' => 'Specific notes or instructions for the driver (e.g., placement details, gate codes).'],
                    'equipment_details' => [
                        'type' => 'array',
                        'description' => 'A list of all equipment items for the rental. Required if service_type is "equipment_rental".',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'equipment_name' => ['type' => 'string', 'description' => 'The specific name and size of the equipment, e.g., "20-yard dumpster", "Standard portable toilet", "ADA-compliant portable toilet".'],
                                'quantity' => ['type' => 'integer', 'description' => 'The number of units for this equipment item.'],
                                'duration_days' => ['type' => 'integer', 'description' => 'The rental period in days for this item.'],
                                'estimated_weight_tons' => ['type' => 'number', 'description' => 'Estimated weight in tons. Applicable only to dumpsters.'],
                                'specific_needs' => ['type' => 'string', 'description' => 'Any other specific requirements noted by the user for this item.']
                            ],
                            'required' => ['equipment_name', 'quantity', 'duration_days']
                        ]
                    ],
                    'junk_details' => [
                        'type' => 'object',
                        'description' => 'Details for a junk removal job. Required if service_type is "junk_removal".',
                        'properties' => [
                            'junk_description' => ['type' => 'string', 'description' => 'A general description from the user of the items to be removed.'],
                            'junk_location_in_property' => ['type' => 'string', 'description' => 'Where the junk is located on the property, e.g., "Curbside", "Backyard", "Basement", "Garage". Critical for pricing.'],
                            'has_easy_access' => ['type' => 'boolean', 'description' => 'Inferred boolean indicating if the driver can easily access the junk.'],
                            'media_urls' => [
                                'type' => 'array',
                                'description' => 'An array of URLs to images or videos the user uploaded to show the junk.',
                                'items' => ['type' => 'string', 'format' => 'uri']
                            ]
                        ],
                        'required' => ['junk_description', 'junk_location_in_property']
                    ]
                ],
                'required' => ['service_type', 'customer_type', 'customer_name', 'customer_email', 'customer_phone', 'location', 'service_date']
            ]
        ]
    ]
];