<?php
/**
 * includes/chatbot-knowledge.php
 * ------------------------------------------------------------------
 * Knowledge base for the Fresh Lettuce Farm AI Customer Service Assistant.
 *
 * This file is pure data (arrays), kept separate from the matching
 * logic in chatbot-engine.php on purpose: adding a new FAQ, product
 * topic, or routing rule should never require touching the engine
 * code, just appending an entry to one of the arrays below.
 *
 * Structure
 * ---------
 * CHATBOT_SERVICES   — the 6 customer service modules the bot must
 *                       help customers choose between. Each has the
 *                       keywords that suggest it, an explanation, and
 *                       where it sends the customer.
 * CHATBOT_KNOWLEDGE  — general knowledge-base topics (company info,
 *                       product info, lettuce care, shipping,
 *                       orders, account help, FAQ, etc). Each entry:
 *                       'keywords' => words/phrases that trigger it
 *                       'answer'   => the conversational reply
 * CHATBOT_QUICK_ACTIONS — buttons shown when the chat opens; each
 *                       maps to a canned message that is run through
 *                       the exact same engine a typed message would
 *                       be, so behavior never diverges from typing.
 * ------------------------------------------------------------------
 */

return [

    // =================================================================
    // 1a. SYNONYMS — common variations/synonyms customers actually type,
    //     mapped to one canonical word/phrase. The engine rewrites
    //     incoming messages using this map before matching, so keyword
    //     lists in 'services' and 'knowledge' below don't need to spell
    //     out every possible phrasing of the same idea.
    //     Longer phrases are listed first so they're replaced before
    //     their shorter sub-phrases.
    // =================================================================
    'synonyms' => [
        // Lettuce / produce language
        'romaine' => 'romaine lettuce',
        'iceberg' => 'iceberg lettuce',
        'butterhead' => 'butterhead lettuce',
        'green leaf' => 'green leaf lettuce',
        'red leaf' => 'red leaf lettuce',
        'oakleaf' => 'oakleaf lettuce',
        'lollo rosso' => 'lollo rosso lettuce',
        'salad mix' => 'baby lettuce mix',
        'baby greens' => 'baby lettuce mix',
        'mixed greens' => 'baby lettuce mix',

        // Quality / freshness language
        'wilted' => 'wilted lettuce',
        'wilting' => 'wilted lettuce',
        'not fresh' => 'stale lettuce',
        'stale' => 'stale lettuce',
        'brown spots' => 'damaged lettuce',
        'bruised' => 'damaged lettuce',
        'slimy' => 'damaged lettuce',
        'mushy' => 'damaged lettuce',
        'crisp' => 'fresh lettuce',

        // Delivery language
        'ship' => 'delivery',
        'shipping' => 'delivery',
        'courier' => 'delivery',
        'bring' => 'delivery',
        'send' => 'delivery',
        'track my package' => 'track my order',
        'where is my package' => 'track my order',
        "where's my order" => 'track my order',
        'my package' => 'my order',
        'my shipment' => 'my order',

        // Order language
        'purchase' => 'order',
        'buy' => 'order',
        'checkout' => 'order',

        // Money language
        'get my money back' => 'refund',
        'want my money back' => 'refund',
        'give me a refund' => 'refund',
        'reimbursement' => 'refund',
        'reimburse me' => 'refund',
        'price' => 'pricing',

        // Account language
        'sign in' => 'log in',
        'signing in' => 'logging in',
        'log-in' => 'log in',
        'my password isn\'t working' => 'forgot password',
        "i can't remember my password" => 'forgot password',
        'cant remember my password' => 'forgot password',
        'create a new account' => 'create an account',
        'make an account' => 'create an account',
        'sign up for an account' => 'create an account',
    ],

    // =================================================================
    // 1b. REACTIVATION — phrases that bring the AI assistant back into
    //     a conversation after a customer asked for (or was escalated
    //     to) a live agent.
    // =================================================================
    'reactivate_keywords' => [
        'talk to the assistant', 'talk to assistant', 'chat with the assistant',
        'chat with assistant', 'assistant again', 'back to the bot', 'back to the assistant',
        'talk to the bot', 'chat with the bot', 'stop waiting for an agent',
        "don't need an agent", 'dont need an agent', 'never mind the agent',
        'no more agent', 'back to the chatbot', 'continue with assistant',
    ],

    // =================================================================
    // 1c. TOPIC WORD ALIASES — bare/short follow-up words ("romaine",
    //     "organic", "delivery", "payment") that customers type as a
    //     one- or two-word follow-up. The engine only consults this map
    //     as a last-resort fallback, after normal keyword matching has
    //     already come up empty.
    //     Value is the 'id' of the matching knowledge entry above.
    // =================================================================
    'topic_word_aliases' => [
        // Product varieties
        'romaine' => 'romaine_info',
        'iceberg' => 'iceberg_info',
        'butterhead' => 'butterhead_info',
        'baby mix' => 'baby_mix_info',
        'green leaf' => 'green_leaf_info',
        'red leaf' => 'red_leaf_info',
        'oakleaf' => 'oakleaf_info',
        'lollo rosso' => 'lollo_rosso_info',
        'butterhead' => 'butterhead_info',
        'salad mix' => 'baby_mix_info',

        // Product categories
        'organic' => 'organic_info',
        'hydroponic' => 'hydroponic_info',
        'whole lettuce' => 'whole_lettuce_info',

        // Service follow-ups
        'delivery' => 'delivery_info',
        'shipping' => 'delivery_info',
        'payment' => 'payment_info',
        'pay' => 'payment_info',
        'cod' => 'payment_info',
        'gcash' => 'payment_info',
        'maya' => 'payment_info',
        'bank transfer' => 'payment_info',

        // Account follow-ups
        'account' => 'account_overview',
        'password' => 'forgot_password',
        'passwords' => 'forgot_password',
        'login' => 'login_help',
        'log in' => 'login_help',
        'logging in' => 'login_help',
        'register' => 'create_account',
        'registering' => 'create_account',
        'profile' => 'edit_profile',
        'my profile' => 'edit_profile',

        // Freshness follow-ups
        'fresh' => 'freshness_info',
        'freshness' => 'freshness_info',
        'storage' => 'storage_info',
        'store' => 'storage_info',
        'shelf life' => 'shelf_life_info',
        'keep' => 'storage_info',
    ],

    // =================================================================
    // 1d. REFERENTIAL PRONOUNS — used to detect when a short follow-up
    //     ("what does it taste like?", "are they organic?") is pointing
    //     back at whatever the assistant just answered.
    // =================================================================
    'referential_pronouns' => ['it', 'this', 'that', 'they', 'them', 'those', 'these'],

    // =================================================================
    // 1e. GREETINGS — short opening messages ("hi", "good morning")
    //     get a friendly, varied welcome instead of being run through
    //     intent matching.
    // =================================================================
    'greetings' => [
        'hi', 'hello', 'hey', 'hiya', 'yo', 'hey there', 'hi there', 'hello there',
        'good morning', 'good afternoon', 'good evening', 'greetings', 'sup',
        "what's up", 'whats up', 'good day', 'howdy', 'hola',
    ],
    'greeting_responses' => [
        "Hi there! 👋 Welcome to Fresh Lettuce Farm! How can I help you today?",
        "Hello! 🌱 What can I do for you today? Fresh lettuce is our specialty!",
        "Hey there! 🥬 I'm here to help with orders, products, delivery, or pointing you to the right form. What do you need?",
        "Hi! 🥗 I'm your Fresh Lettuce Assistant — I can help with product info, order tracking, delivery questions, and more. What's on your mind?",
    ],

    // =================================================================
    // 1f. GRATITUDE — "thanks", "thank you", etc.
    // =================================================================
    'gratitude' => [
        'thanks', 'thank you', 'thanks a lot', 'thank you so much', 'thanks so much',
        'appreciate it', 'i appreciate it', 'much appreciated', 'many thanks',
        'ty', 'thx', 'thank you very much', 'cheers',
    ],
    'gratitude_responses' => [
        "You're very welcome! 🌱 Enjoy your fresh lettuce! Let me know if there's anything else I can help with.",
        "Happy to help! 🥬 Feel free to reach out anytime you have another question about our products.",
        "Anytime! 🥗 Is there anything else I can help you with today?",
        "You're welcome — glad I could help! 🥬 Don't forget to check out our weekly specials!",
    ],

    // =================================================================
    // 1g. YES/NO WORDS — used to interpret a short reply to a question
    //     the assistant itself just asked.
    // =================================================================
    'affirmative_words' => [
        'yes', 'yeah', 'yep', 'yup', 'sure', 'okay', 'ok', 'please', 'go ahead',
        'sounds good', 'do it', 'yes please', 'of course', 'definitely', 'please do',
        'yes please guide me', 'alright', 'yes show me', 'okay sure',
    ],
    'negative_words' => [
        'no', 'nope', 'nah', 'not now', 'no thanks', 'not really', 'never mind',
        'no thank you', "i'm good", 'im good', 'maybe later', 'not right now',
        'later', 'not yet',
    ],

    // =================================================================
    // 1h. CONTEXTUAL WORD ALIASES — like 'topic_word_aliases' above, but
    //     scoped to the current conversation's domain.
    // =================================================================
    'contextual_word_aliases' => [
        'return' => [
            'processing time' => 'return_processing',
            'how long' => 'return_processing',
            'refund time' => 'return_processing',
            'replacement' => 'replacement_info',
            'exchange' => 'replacement_info',
            'inspection' => 'return_inspection',
            'damaged item' => 'svc:return',
            'eligibility' => 'return_eligibility',
            'eligible' => 'return_eligibility',
            'window' => 'return_window',
            'deadline' => 'return_window',
        ],
        'delivery' => [
            'delivery time' => 'delivery_time',
            'delivery areas' => 'delivery_locations',
            'shipping fees' => 'delivery_fee',
            'fees' => 'delivery_fee',
            'track order' => 'track_delivery',
            'tracking' => 'track_delivery',
            'location' => 'delivery_locations',
            'area' => 'delivery_locations',
        ],
        'freshness' => [
            'storage' => 'storage_info',
            'store' => 'storage_info',
            'shelf life' => 'shelf_life_info',
            'keep fresh' => 'storage_info',
            'refrigerator' => 'storage_info',
            'fridge' => 'storage_info',
        ],
    ],

    // =================================================================
    // 1i. FALLBACK RESPONSES — used when everything else comes up empty.
    // =================================================================
    'fallback_responses' => [
        "I'm not sure I understand that yet — could you rephrase, or tell me a bit more about what's going on? For example: is this about an order, a product, delivery, or something else?",
        "Hmm, I'm not quite following. Could you tell me a little more — is this about a product, an order, your account, or something else? You can also tap one of the quick actions above.",
        "I want to make sure I point you in the right direction — could you describe what's going on in a bit more detail? Or tap one of the quick actions above and I'll take it from there.",
        "I didn't quite catch what you need there. Could you rephrase that, or let me know if it's about an order, a product, delivery, or your account?",
    ],

    // =================================================================
    // 2. CUSTOMER SERVICE MODULES
    // =================================================================
    'services' => [

        'support_ticket' => [
            'label' => 'Support Ticket',
            'requires_login' => true,
            'keywords' => [
                'support ticket', 'submit a ticket', 'open a ticket', 'raise a ticket',
                'general concern', 'technical issue', 'technical problem', 'technical question',
                'account problem', 'question about my order', 'general question about my order',
                'issue with my account', 'follow up', 'followup', 'billing issue',
                'charged incorrectly', 'website issue', 'app issue', 'general question',
            ],
            'explain' => "A Support Ticket is what you'll want for a general concern, a technical question, an account issue, a billing problem, or anything else that isn't specifically tied to product quality (Freshness) or delivery issues.\n\nUse it when: you have a general question, account issue, billing concern, or anything that needs ongoing follow-up.\nDon't use it for: quality issues with delivered lettuce (that's Freshness) or delivery problems (that's Returns/Refunds).",
            'action' => "To submit a support ticket, go to the Submit a Ticket page while logged into your Fresh Lettuce Farm account.\n\nBefore submitting, please prepare:\n\n• Subject\n• Category\n• A clear description of your issue\n• Order Number (if applicable)\n• Photos or supporting files (optional)\n\nOnce submitted, our support team will review your ticket and keep you updated.",
            'requirements' => ['Subject', 'Category', 'Description of the issue', 'Order Number (optional)', 'Photos or supporting files (optional)'],
            'link' => 'admin-support-tickets.html',
            'link_label' => 'Go to Support Tickets',
        ],

        'return' => [
            'label' => 'Return & Refund Request',
            'requires_login' => true,
            'domain' => 'return',
            'keywords' => [
                'return', 'refund', 'wrong item', 'damaged on delivery', 'damaged upon delivery',
                'arrived damaged', 'arrived wilted', 'arrived with brown spots',
                'wilted on arrival', 'slimy lettuce', 'mushy lettuce',
                'missing parts', 'wrong order', 'incorrect order', 'send it back',
                'money back', 'replacement', 'arrived defective', 'came damaged',
                'delivered damaged', 'damaged in transit', 'received the wrong',
                'sent me the wrong', "wasn't what i ordered", 'not what i ordered',
                'quality issue on delivery', 'freshness issue on arrival',
            ],
            'explain' => "A Return & Refund Request is for problems tied to the order itself — something wrong that was true right from delivery.\n\nUse it when: the wrong product was sent, your lettuce arrived wilted, damaged, or spoiled, you received the wrong order entirely, or you're not satisfied with the quality upon delivery.\n\nDon't use it for: general questions (that's Support Ticket) or quality issues that developed after days of proper storage (that's Freshness feedback).",
            'action' => "To submit a Return & Refund Request:\n\nCustomer Dashboard → Return Request\n\nHave ready:\n\n• Order Number\n• Product Name\n• Date of Delivery\n• Reason for Return\n• Detailed Description of the Issue\n• Photos of the issue (very helpful)\n• Whether you prefer a refund or replacement",
            'requirements' => ['Order Number', 'Product Name', 'Date of Delivery', 'Reason for Return', 'Description of the Issue', 'Photos (recommended)'],
            'link' => 'admin-return-refund.html',
            'link_label' => 'Go to Return & Refund Request',
        ],

        'freshness' => [
            'label' => 'Freshness & Quality',
            'requires_login' => false,
            'domain' => 'freshness',
            'keywords' => [
                'freshness', 'quality', 'wilted lettuce', 'wilted', 'not fresh',
                'stale lettuce', 'brown spots', 'bruised lettuce', 'slimy lettuce',
                'quality issue', 'freshness problem', 'lettuce quality',
                'is the lettuce fresh', 'how fresh', 'spoiled', 'went bad',
                'not crispy', 'soft lettuce', 'mushy',
            ],
            'explain' => "Freshness & Quality is for concerns about the condition of your lettuce after proper storage, or questions about our quality standards.\n\nUse it when: you want to know about our freshness guarantee, ask about quality standards, or report quality concerns.\n\nDon't use it for: delivery issues (that's Return/Refund) or if the issue was present upon delivery (that's also Return/Refund).",
            'action' => "To report a freshness concern:\n\n• Provide your order number\n• Describe the quality issue in detail\n• Share photos of the product\n• Let us know how it was stored\n\nOur team will investigate and get back to you promptly.",
            'requirements' => ['Order Number', 'Description of the quality issue', 'Photos of the product', 'Storage method used'],
            'link' => 'admin-feedback.html',
            'link_label' => 'Go to Feedback',
        ],

        'contact_support' => [
            'label' => 'Contact Support',
            'keywords' => [
                'business inquiry', 'partnership', 'suggestion', 'general inquiry',
                'non-urgent', 'wholesale', 'press', 'media', 'collaborate',
                'sponsorship', 'contact support', 'contact you', 'how do i contact',
                'phone number', 'email address', 'call you', 'reach you',
                'contact info', 'customer service',
            ],
            'explain' => "Contact Support is best for general inquiries that aren't tied to a specific order — things like business concerns, partnership requests, suggestions, or other non-urgent questions.\n\nUse it when: you have a non-urgent, general, or business-related question.\nDon't use it for: order-specific issues, freshness concerns, or returns — those move faster through their own forms.\n\nHere's how to reach us directly:\n\n📞 Call: +63-912-345-6789\n   Mon–Sat, 8:00 AM–6:00 PM\n\n📧 Email: info@freshlettuce.com — we reply within 24 hours\n\n💬 Or stay right here in Live Chat for anything more immediate",
            'action' => null,
            'link' => 'contact-us.html',
            'link_label' => 'Go to Contact Support',
        ],

        'feedback' => [
            'label' => 'Feedback',
            'keywords' => [
                'feedback', 'compliment', 'great job', 'suggestion for the website',
                'love the site', 'website improvement', 'happy with', 'satisfied',
                'wanted to say thanks', 'rate my experience', 'leave feedback',
                'review', 'rate us', 'tell us what you think',
            ],
            'explain' => "Feedback is where compliments, suggestions, and thoughts on your overall experience go.\n\nUse it when: you want to compliment us, suggest an improvement, or rate your experience.\nDon't use it for: reporting a problem — if something's actually wrong, I'll route you to the right form so it actually gets resolved.",
            'action' => "To share your feedback:\n\n• Go to the Feedback page\n• Rate your experience (1-5 stars)\n• Share your comments\n• Let us know how we can improve\n\nIt only takes a minute and helps us serve you better!",
            'link' => 'feedback.html',
            'link_label' => 'Go to Feedback',
        ],

        'live_agent' => [
            'label' => 'Live Chat (human agent)',
            'keywords' => [
                'agent', 'human', 'real person', 'representative', 'talk to someone',
                'speak to someone', 'speak with a person', 'live agent', 'talk to a human',
                'customer service', 'real agent', 'person', 'human support',
            ],
            'explain' => "Live Chat connects you with a Fresh Lettuce Farm support representative for immediate questions, quick assistance, or anything I can't fully resolve myself.",
            'action' => null,
            'link' => null,
            'link_label' => null,
        ],
    ],

    // =================================================================
    // 3. GENERAL KNOWLEDGE BASE
    // =================================================================
    'knowledge' => [

        // --- Company information -----------------------------------
        [
            'keywords' => ['business hours', 'store hours', 'customer service hours', 'open hours', 'what time do you open', 'when are you open', 'operating hours'],
            'answer' => "Our customer service team is available Monday–Saturday, 8:00 AM–6:00 PM. We're closed on Sundays and major holidays.\n\nDelivery hours: 6:00 AM–8:00 PM, Monday–Saturday.\n\nLive Chat and Support Tickets are monitored throughout these hours.",
        ],
        [
            'keywords' => ['contact information', 'how do i contact you', 'contact details'],
            'answer' => "You can reach us a few ways:\n\n📞 Call: +63-912-345-6789 (Mon–Sat, 8:00 AM–6:00 PM)\n📧 Email: info@freshlettuce.com\n💬 Live Chat: right here, anytime\n🎫 Support Ticket: for anything tied to your account or an order\n\nWhat would you like help with?",
        ],
        [
            'keywords' => ['about fresh lettuce farm', 'who are you', 'what is fresh lettuce farm', 'about the company', 'about your farm', 'tell me about yourself'],
            'answer' => "Fresh Lettuce Farm is a hydroponic lettuce farm based in Benguet, Philippines. We specialize in growing fresh, organic lettuce using sustainable hydroponic methods.\n\n🌱 **What makes us special:**\n• Harvested daily for maximum freshness\n• 100% pesticide-free and chemical-free\n• Delivered within 24 hours of harvest\n• Hydroponically grown for superior quality\n• Committed to sustainability and community\n\nWe've been serving customers since 2018 and are proud to be one of the leading hydroponic lettuce farms in the Philippines!",
        ],

        // --- Product information ------------------------------------
        [
            'id' => 'product_overview',
            'group' => 'overview',
            'display' => 'our lettuce products',
            'keywords' => ['what products', 'what do you sell', 'product types', 'tell me about your products', 'product information', 'what kind of lettuce', 'lettuce products', 'your products'],
            'answer' => "We offer a wide variety of fresh, hydroponic lettuce!\n\n🌱 **Our product categories:**\n• Whole Lettuce (Romaine, Iceberg, Butterhead, and more)\n• Organic Lettuce\n• Salad Mixes (Baby Lettuce Mix)\n• Bundle Packs (Family Salad Pack, Weekly Fresh Box)\n• Wholesale (Restaurant Pack, Wholesale Box)\n\n🥬 **Popular varieties:**\n• Romaine Lettuce - ₱120\n• Iceberg Lettuce - ₱100\n• Butterhead Lettuce - ₱150\n• Baby Lettuce Mix - ₱180\n• Family Salad Pack - ₱350\n\nWould you like to know more about any specific variety — just tell me which one (e.g. \"Romaine\" or \"Baby Mix\")!",
        ],
        [
            'id' => 'romaine_info',
            'group' => 'category',
            'display' => 'Romaine Lettuce',
            'keywords' => ['romaine', 'tell me about romaine', 'romaine lettuce', 'organic romaine', 'romaine variety'],
            'answer' => "🥬 **Organic Romaine Lettuce**\n\nOur Organic Romaine Lettuce is grown using advanced hydroponic systems. It's known for its crisp texture and deep green, flavorful leaves.\n\n• **Price:** ₱120\n• **Weight:** 200g\n• **Harvested:** Daily\n• **Nutrition:** Rich in vitamins A, C, and K\n• **Ideal for:** Caesar salads, sandwiches, wraps, and grilling\n\n**Taste Profile:** Crisp, slightly sweet, with a satisfying crunch\n\nWould you like to know more about our other lettuce varieties?",
        ],
        [
            'id' => 'iceberg_info',
            'group' => 'category',
            'display' => 'Iceberg Lettuce',
            'keywords' => ['iceberg', 'tell me about iceberg', 'iceberg lettuce', 'hydroponic iceberg'],
            'answer' => "🥬 **Hydroponic Iceberg Lettuce**\n\nOur Iceberg Lettuce is grown hydroponically for maximum purity and crunch. It's the classic crisphead lettuce that's perfect for burgers and sandwiches.\n\n• **Price:** ₱100\n• **Weight:** 250g\n• **Harvested:** Daily\n• **Nutrition:** Good source of vitamin A and K\n• **Ideal for:** Burgers, sandwiches, salads, and wraps\n\n**Taste Profile:** Mild, refreshing, very crisp\n\nWould you like to know more about our other lettuce varieties?",
        ],
        [
            'id' => 'butterhead_info',
            'group' => 'category',
            'display' => 'Butterhead Lettuce',
            'keywords' => ['butterhead', 'tell me about butterhead', 'butterhead lettuce', 'premium butterhead'],
            'answer' => "🥬 **Premium Butterhead Lettuce**\n\nOur Butterhead Lettuce is known for its soft, buttery texture and mild, sweet flavor. It forms a loose head with delicate leaves.\n\n• **Price:** ₱150\n• **Weight:** 200g\n• **Harvested:** Daily\n• **Nutrition:** Excellent source of Vitamin A and K\n• **Ideal for:** Gourmet salads, lettuce wraps, and elegant presentations\n\n**Taste Profile:** Soft, buttery, mild, and sweet\n\nThis is one of our most popular varieties! Would you like to know more?",
        ],
        [
            'id' => 'baby_mix_info',
            'group' => 'category',
            'display' => 'Baby Lettuce Mix',
            'keywords' => ['baby mix', 'baby lettuce mix', 'salad mix', 'tell me about baby mix', 'baby greens'],
            'answer' => "🥗 **Baby Lettuce Mix**\n\nA delightful mix of tender, young lettuce leaves that are perfect for fresh salads. This mix includes a variety of colors and textures.\n\n• **Price:** ₱180\n• **Weight:** 150g\n• **Harvested:** Daily\n• **Nutrition:** A rich blend of vitamins and minerals\n• **Ideal for:** Fresh salads, garnishes, and gourmet dishes\n\n**Varieties included:** Romaine, Green Leaf, Red Leaf, Oakleaf, and more!\n\n**Taste Profile:** Mild, sweet, with a mix of textures\n\nWould you like to see our other salad options?",
        ],
        [
            'id' => 'green_leaf_info',
            'group' => 'category',
            'display' => 'Green Leaf Lettuce',
            'keywords' => ['green leaf', 'green leaf lettuce', 'tell me about green leaf'],
            'answer' => "🥬 **Green Leaf Lettuce**\n\nOur Green Leaf Lettuce features tender, ruffled leaves with a mild, sweet flavor. It's a versatile variety that works well in many dishes.\n\n• **Price:** ₱110\n• **Weight:** 200g\n• **Harvested:** Daily\n• **Nutrition:** Rich in vitamins A and K\n• **Ideal for:** Salads, sandwiches, and wraps\n\n**Taste Profile:** Mild, sweet, tender\n\nWould you like to know more about our other lettuce varieties?",
        ],
        [
            'id' => 'red_leaf_info',
            'group' => 'category',
            'display' => 'Red Leaf Lettuce',
            'keywords' => ['red leaf', 'red leaf lettuce', 'tell me about red leaf'],
            'answer' => "🥬 **Red Leaf Lettuce**\n\nOur Red Leaf Lettuce features beautiful burgundy leaves with a mild, slightly nutty flavor. It adds color and nutrition to any dish.\n\n• **Price:** ₱130\n• **Weight:** 200g\n• **Harvested:** Daily\n• **Nutrition:** Rich in antioxidants and vitamins\n• **Ideal for:** Salads, sandwiches, and as a colorful garnish\n\n**Taste Profile:** Mild, slightly nutty, tender\n\nWould you like to know more about our other lettuce varieties?",
        ],
        [
            'id' => 'oakleaf_info',
            'group' => 'category',
            'display' => 'Oakleaf Lettuce',
            'keywords' => ['oakleaf', 'oakleaf lettuce', 'tell me about oakleaf'],
            'answer' => "🥬 **Oakleaf Lettuce**\n\nOur Oakleaf Lettuce is named for its distinctive oak-leaf shape. It has a mild, sweet flavor and a tender texture.\n\n• **Price:** ₱140\n• **Weight:** 200g\n• **Harvested:** Daily\n• **Nutrition:** Rich in vitamins A and K\n• **Ideal for:** Mixed salads, gourmet dishes, and as a wrap\n\n**Taste Profile:** Mild, sweet, tender\n\nWould you like to know more about our other lettuce varieties?",
        ],
        [
            'id' => 'lollo_rosso_info',
            'group' => 'category',
            'display' => 'Lollo Rosso Lettuce',
            'keywords' => ['lollo rosso', 'lollo rosso lettuce', 'tell me about lollo rosso'],
            'answer' => "🥬 **Lollo Rosso Lettuce**\n\nOur Lollo Rosso Lettuce features frilly, burgundy-red leaves with a mild, slightly nutty flavor. It's a stunning addition to any salad.\n\n• **Price:** ₱160\n• **Weight:** 200g\n• **Harvested:** Daily\n• **Nutrition:** Rich in antioxidants and vitamins\n• **Ideal for:** Gourmet salads, garnishes, and colorful dishes\n\n**Taste Profile:** Mild, slightly nutty, crisp\n\nWould you like to know more about our other lettuce varieties?",
        ],
        [
            'id' => 'organic_info',
            'group' => 'category',
            'display' => 'Organic Lettuce',
            'keywords' => ['organic', 'organic lettuce', 'is it organic', 'organic certification', 'pesticide free', 'chemical free'],
            'answer' => "🌱 **100% Organic Lettuce**\n\nYes! We offer both organic and conventional options:\n\n• **Organic Range:** Grown without pesticides or chemicals\n• **Certified:** Our organic products are certified\n• **Conventional:** Grown with minimal, safe inputs\n• **Hydroponic:** All products are hydroponically grown\n\n**What this means for you:**\n• No harmful chemicals on your food\n• Better nutrition and flavor\n• Environmentally sustainable farming\n\nOur organic varieties include Romaine, Green Leaf, and Butterhead. Would you like to know more about a specific organic variety?",
        ],
        [
            'id' => 'hydroponic_info',
            'group' => 'category',
            'display' => 'Hydroponic Growing',
            'keywords' => ['hydroponic', 'hydroponically grown', 'how is it grown', 'growing method', 'farming method'],
            'answer' => "🌊 **Hydroponic Growing Method**\n\nOur lettuce is grown using advanced hydroponic systems:\n\n**What is hydroponics?**\n• Plants grow in nutrient-rich water instead of soil\n• No pesticides or herbicides needed\n• 90% less water than traditional farming\n• Year-round growing regardless of weather\n\n**Benefits for you:**\n• Cleaner, pesticide-free produce\n• Consistent quality year-round\n• More nutritious and flavorful lettuce\n• Environmentally sustainable\n\n**Our Process:**\n1. Premium seeds are carefully selected\n2. Seedlings grow in our nursery\n3. Plants thrive in nutrient-rich water\n4. Harvested daily at peak freshness\n5. Delivered to your doorstep within 24 hours\n\nWould you like to know more about our farming process?",
        ],
        [
            'id' => 'whole_lettuce_info',
            'group' => 'category',
            'display' => 'Whole Lettuce',
            'keywords' => ['whole lettuce', 'whole head', 'head lettuce', 'what is whole lettuce'],
            'answer' => "🥬 **Whole Lettuce**\n\nOur Whole Lettuce varieties are harvested as intact heads, keeping them fresh for longer.\n\n**Available varieties:**\n• Romaine Lettuce - ₱120\n• Iceberg Lettuce - ₱100\n• Butterhead Lettuce - ₱150\n• Green Leaf Lettuce - ₱110\n• Red Leaf Lettuce - ₱130\n• Oakleaf Lettuce - ₱140\n• Lollo Rosso Lettuce - ₱160\n\n**Why choose whole lettuce?**\n• Stays fresh longer than cut varieties\n• More versatile for different dishes\n• You control how much you use\n• Better value for regular lettuce eaters\n\nWould you like to know more about a specific variety?",
        ],

        // --- Freshness & Quality -----------------------------------
        [
            'id' => 'freshness_info',
            'domain' => 'freshness',
            'keywords' => ['how fresh is the lettuce', 'freshness guarantee', 'quality of lettuce', 'is the lettuce fresh', 'freshness standards', 'harvested when'],
            'answer' => "🌱 **Freshness Guarantee**\n\nWe take freshness very seriously!\n\n• **Harvested Daily** - Every morning before delivery\n• **Delivered within 24 hours** - From farm to your table\n• **Hydroponically grown** - Clean and pesticide-free\n• **Temperature-controlled** - Kept fresh during transport\n• **Quality checked** - Before dispatch\n\n**Our Promise:**\nWe guarantee freshness for 5-7 days when properly refrigerated at 2-4°C.\n\nIf your lettuce doesn't meet our freshness standards, we'll make it right!\n\nWould you like storage tips to keep your lettuce fresh longer?",
        ],
        [
            'id' => 'storage_info',
            'domain' => 'freshness',
            'keywords' => ['storage', 'how to store', 'keep fresh', 'refrigerate', 'fridge', 'store lettuce', 'storage tips'],
            'answer' => "🥬 **Storage Tips for Fresh Lettuce**\n\nTo keep your lettuce fresh longer:\n\n• Store in the refrigerator at 2-4°C\n• Keep in the crisper drawer with high humidity\n• Don't wash until you're ready to use it\n• Wrap in a paper towel to absorb moisture\n• Keep away from fruits that produce ethylene (like apples and bananas)\n\n**Pro Tip:**\nTo revive slightly wilted lettuce, soak in cold water for 10-15 minutes. Pat dry with paper towels before using.\n\n**Shelf Life Guide:**\n• Whole head lettuce: 5-7 days\n• Loose leaf lettuce: 3-5 days\n• Pre-cut salad mix: 2-3 days\n\nWould you like more specific tips for a particular variety?",
        ],
        [
            'id' => 'shelf_life_info',
            'domain' => 'freshness',
            'keywords' => ['shelf life', 'how long does it last', 'freshness duration', 'how long does lettuce stay fresh', 'expire'],
            'answer' => "📅 **Lettuce Shelf Life Guide**\n\nOur lettuce typically stays fresh for:\n\n• **Whole head lettuce:** 5-7 days when properly refrigerated\n• **Loose leaf lettuce:** 3-5 days\n• **Pre-cut salad mix:** 2-3 days\n\n**Signs of freshness:**\n• Crisp, vibrant leaves\n• Bright color\n• Fresh, earthy smell\n\n**Signs it's time to discard:**\n• Wilted or limp leaves\n• Brown or black spots\n• Slimy texture\n• Off or sour smell\n\n**Pro Tip:** Don't wash your lettuce until you're ready to use it — washing removes natural protective barriers and can cause quicker spoilage.\n\nWould you like specific storage instructions for a particular variety?",
        ],

        // --- Delivery ----------------------------------------------
        [
            'id' => 'delivery_info',
            'domain' => 'delivery',
            'keywords' => ['delivery', 'shipping', 'delivery fee', 'shipping cost', 'how much is delivery', 'delivery time', 'shipping time'],
            'answer' => "🚚 **Delivery Information**\n\n**Delivery Options:**\n• Standard Delivery: ₱50 (1-2 days)\n• Express Delivery: ₱150 (within 4 hours)\n• Free Delivery: For orders ₱1,000+\n• Farm Pickup: Free\n\n**Delivery Hours:**\n6:00 AM - 8:00 PM, Monday to Saturday\n\n**Delivery Areas:**\nWe deliver to Metro Manila and nearby provinces.\n\n**Selected delivery dates are available during checkout.**\n\nWould you like to check delivery availability in your area?",
        ],
        [
            'id' => 'delivery_time',
            'domain' => 'delivery',
            'keywords' => ['delivery time', 'how long is shipping', 'when will it arrive', 'shipping time', 'when will i get my order'],
            'answer' => "⏰ **Delivery Times**\n\nDelivery times depend on your chosen option:\n\n• **Standard Delivery:** 1-2 business days\n• **Express Delivery:** Within 4 hours\n• **Same-day Delivery:** Orders placed before 10 AM\n• **Next-day Delivery:** Orders placed after 10 AM\n\n**You'll receive:**\n• Order confirmation immediately\n• Tracking updates via email\n• SMS notification when your order is out for delivery\n\nWould you like to know more about tracking your order?",
        ],
        [
            'id' => 'delivery_locations',
            'domain' => 'delivery',
            'keywords' => ['delivery locations', 'do you ship to', 'delivery areas', 'where do you deliver', 'service area'],
            'answer' => "📍 **Delivery Areas**\n\nWe currently deliver to:\n\n**Metro Manila:**\n• All cities in Metro Manila\n\n**Nearby Areas:**\n• Caloocan, Malabon, Navotas, Valenzuela\n• Quezon City, Marikina, Pasig, Mandaluyong\n• Makati, Taguig, Pasay, Paranaque\n• Muntinlupa, Las Pinas, Bacoor, Imus\n• Antipolo, Taytay, Cainta\n\n**Expanding Regularly!**\nWe're always expanding our delivery areas — enter your address at checkout to check availability in your location.",
        ],
        [
            'id' => 'delivery_fee',
            'domain' => 'delivery',
            'keywords' => ['delivery fee', 'shipping cost', 'how much is shipping', 'shipping fee', 'delivery cost'],
            'answer' => "💰 **Delivery Fees**\n\nOur delivery fees are simple and transparent:\n\n• **Standard Delivery:** ₱50.00\n• **Express Delivery:** ₱150.00\n• **Free Delivery:** For orders ₱1,000 and above\n• **Farm Pickup:** Free\n\n**Why Free Delivery?**\nWe believe in making fresh, healthy food accessible. That's why we offer free delivery on orders over ₱1,000!\n\nWould you like to know more about our delivery options?",
        ],
        [
            'id' => 'track_delivery',
            'domain' => 'delivery',
            'keywords' => ['track my delivery', 'tracking number', 'track shipment', 'where is my order', 'order status'],
            'answer' => "📦 **Track Your Delivery**\n\nYou can track your order in real-time:\n\n**How to track:**\n1. Log into your account\n2. Go to \"Orders\"\n3. Click \"Track\" on your order\n4. View the real-time status\n\n**You'll see these statuses:**\n1. ✅ Confirmed - Order received\n2. 🌱 Harvesting - Fresh lettuce being harvested\n3. 📦 Packing - Order being packed\n4. ✅ Ready for Delivery - Ready to ship\n5. 🚚 Out for Delivery - On its way\n6. 🏠 Delivered - Successfully delivered!\n\n**Tracking Number:**\nYou'll receive a tracking number via email once your order ships.\n\nWould you like to know more about our delivery process?",
        ],

        // --- Order information ------------------------------------
        [
            'keywords' => ['minimum order', 'minimum amount', 'order minimum', 'minimum purchase'],
            'answer' => "There is no minimum order amount for deliveries. However, orders below ₱500 may incur a small delivery fee.\n\nFor free delivery, simply reach a minimum of ₱1,000!",
        ],
        [
            'keywords' => ['cancel my order', 'cancel order', 'modify my order', 'change my order', 'edit my order'],
            'answer' => "📝 **Order Changes**\n\nYou can modify or cancel your order within 2 hours of placing it. After that, the order may already be in preparation.\n\n**How to modify or cancel:**\n• Go to your order history in your dashboard\n• Find the order you want to modify\n• Click \"Cancel Order\" or contact support for modifications\n\n**Need help?**\nContact us at +63-912-345-6789 or submit a support ticket.",
        ],
        [
            'keywords' => ['schedule delivery', 'delivery date', 'specific delivery date', 'preferred delivery date'],
            'answer' => "📅 **Scheduled Delivery**\n\nYes! You can select your preferred delivery date during checkout.\n\n**Tips:**\n• We recommend choosing a date at least 1 day in advance\n• You can also specify a preferred time window in the delivery notes\n• You'll receive a confirmation email with your delivery details\n\nWould you like to know more about our delivery options?",
        ],
        [
            'keywords' => ['subscription', 'recurring order', 'weekly box', 'bi-weekly', 'subscribe', 'regular delivery'],
            'answer' => "📦 **Subscription Boxes**\n\nYes! We offer weekly and bi-weekly subscription boxes:\n\n• **Weekly Fresh Box:** Delivered every week\n• **Bi-weekly Box:** Delivered every 2 weeks\n• **Customize your box:** Choose which varieties you want each time\n\n**Benefits:**\n• Save 15% compared to regular orders\n• Never run out of fresh lettuce\n• Flexible delivery schedule\n• Cancel or pause anytime\n\nWould you like to know more about our subscription options?",
        ],

        // --- Payment information ----------------------------------
        [
            'id' => 'payment_info',
            'keywords' => ['payment methods', 'how to pay', 'payment options', 'cash on delivery', 'cod', 'gcash', 'maya', 'bank transfer'],
            'answer' => "💳 **Payment Methods**\n\nWe accept several payment options for your convenience:\n\n• **Cash on Delivery (COD)** - Pay when you receive your order\n• **GCash** - Quick and secure mobile payment\n• **Maya** - Digital wallet payments\n• **Bank Transfer** - Direct bank transfers\n\n**Security:**\nAll payment methods are secure and encrypted. Your financial information is protected.\n\n**Payment Terms:**\n• Full payment is required at checkout\n• COD payments are due upon delivery\n• Payment is non-refundable once order is processed\n\nWould you like to know more about any specific payment method?",
        ],
        [
            'keywords' => ['coupon', 'promo code', 'discount code', 'promotion', 'voucher', 'apply coupon', 'promo'],
            'answer' => "🎉 **Promotions & Coupons**\n\nWe regularly offer promotions and coupon codes!\n\n**How to use a coupon:**\n1. Enter your coupon code in the checkout page\n2. Click \"Apply\" to add the discount\n3. The discount will be reflected in your total\n4. Make sure your order meets the coupon requirements\n\n**Current Promotions:**\n• **FRESH10** - Get 10% off your first order!\n• **Free Delivery** - On orders ₱1,000+\n• **Buy 5 Get 1 Free** - Mix and match any 5 lettuce varieties!\n\nWould you like to know more about our current promotions?",
        ],
        [
            'keywords' => ['gcash', 'pay via gcash', 'gcash payment', 'how to pay with gcash'],
            'answer' => "📱 **GCash Payments**\n\nPaying with GCash is easy and secure:\n\n**How it works:**\n1. Select GCash as your payment method at checkout\n2. You'll be redirected to the GCash payment portal\n3. Confirm the payment on your GCash app\n4. You'll receive a confirmation email once payment is complete\n\n**Benefits:**\n• Fast and convenient\n• No cash handling needed\n• Secure transactions\n• Instant payment confirmation\n\nWould you like to know more about our other payment options?",
        ],
        [
            'keywords' => ['maya', 'pay via maya', 'maya payment', 'paymaya'],
            'answer' => "📱 **Maya Payments**\n\nPaying with Maya (formerly PayMaya) is quick and secure:\n\n**How it works:**\n1. Select Maya as your payment method at checkout\n2. You'll be redirected to the Maya payment portal\n3. Confirm the payment on your Maya app\n4. You'll receive a confirmation email once payment is complete\n\n**Benefits:**\n• Fast and convenient\n• No cash handling needed\n• Secure transactions\n• Instant payment confirmation\n\nWould you like to know more about our other payment options?",
        ],

        // --- Account assistance ------------------------------------
        [
            'id' => 'account_overview',
            'group' => 'overview',
            'display' => 'account help',
            'keywords' => ['i need help with my account', 'account help', 'account assistance', 'account issues'],
            'answer' => "👤 **Account Help**\n\nI can help you with your Fresh Lettuce Farm account.\n\n**What would you like to do?**\n\n• Create a new account\n• Log in to your existing account\n• Reset your forgotten password\n• Change your password\n• Update your profile information\n• Change your email address\n• Update your phone number\n• Manage your delivery addresses\n• View your order history\n\nJust tell me what you need and I'll help you out!",
        ],
        [
            'id' => 'create_account',
            'group' => 'account',
            'auth_aware' => true,
            'display' => 'creating an account',
            'keywords' => ['create an account', 'how do i register', 'sign up', 'registration', 'where do i register', 'where do i sign up', 'make an account'],
            'answer' => "📝 **Creating an Account**\n\nJoining Fresh Lettuce Farm is quick and easy!\n\n**Steps to create an account:**\n1. Go to the Register page\n2. Enter your first and last name\n3. Enter your email address\n4. Create a strong password\n5. Confirm your password\n6. Agree to our Terms & Conditions\n7. Click \"Create Account\"\n\n**Benefits of an account:**\n• Track your orders easily\n• Save your delivery addresses\n• View your order history\n• Access exclusive promotions\n• Faster checkout\n\nWould you like to know more about account features?",
        ],
        [
            'id' => 'login_help',
            'group' => 'account',
            'auth_aware' => true,
            'display' => 'logging in',
            'keywords' => ['how do i log in', 'how to login', 'login help', 'where do i log in', 'where do i login', 'where is the login'],
            'answer' => "🔐 **Logging In**\n\nLogging into your account is simple:\n\n**Steps to log in:**\n1. Go to the Login page\n2. Enter your email address\n3. Enter your password\n4. Click \"Sign In\"\n\n**Remember Me:**\nCheck the \"Remember me\" box to stay logged in on your device (don't use on shared computers).\n\n**Forgot Password?**\nIf you can't remember your password, click the \"Forgot password?\" link and we'll send you a reset link.\n\nNeed help signing in? Just say \"forgot password\" and I'll walk you through the reset process.",
        ],
        [
            'id' => 'forgot_password',
            'group' => 'account',
            'auth_aware' => true,
            'display' => 'resetting a forgotten password',
            'keywords' => [
                'forgot my password', 'forgot password', 'reset my password', 'reset password',
                'password reset', "i can't log in", 'cant log in', 'trouble logging in',
                'help me log in', "i can't access my account", 'cant access my account',
                'login problem', "my password doesn't work", 'my password does not work',
                'password doesnt work', 'i forgot my login', 'forgot my login',
                'need to recover my account', 'recover my account', 'reset my password',
            ],
            'answer' => "🔑 **Reset Your Password**\n\nNo problem! I can help you reset your password.\n\n**Steps to reset your password:**\n1. Go to the Login page\n2. Click the \"Forgot Password?\" link\n3. Enter the email address associated with your account\n4. Click \"Send Reset Link\"\n5. Check your email for the reset link\n6. Click the link to create a new password\n7. Enter and confirm your new password\n8. Log in with your new password\n\n**Need help with any step?**\nJust let me know and I'll guide you through it!\n\nWould you like me to walk you through the process step by step?",
        ],
        [
            'id' => 'change_password',
            'group' => 'account',
            'display' => 'changing your password',
            'keywords' => ['change my password', 'update password', 'change password'],
            'answer' => "🔐 **Change Your Password**\n\nYou can change your password anytime while logged in:\n\n**Steps to change your password:**\n1. Log in to your account\n2. Go to your Profile/Account Settings\n3. Click on \"Change Password\"\n4. Enter your current password\n5. Enter your new password\n6. Confirm your new password\n7. Click \"Save\" or \"Update\"\n\n**Password Requirements:**\n• At least 8 characters\n• Includes uppercase and lowercase letters\n• Includes a number\n• Includes a special character\n\nIf you're logged out and can't remember your current password, use \"forgot password\" instead.\n\nWould you like help with anything else?",
        ],
        [
            'id' => 'edit_profile',
            'group' => 'account',
            'display' => 'updating your profile',
            'keywords' => ['edit my profile', 'update my profile', 'update my details', 'update personal information', 'change my personal information'],
            'answer' => "👤 **Update Your Profile**\n\nYou can update your profile information anytime:\n\n**What you can update:**\n• Full name\n• Email address\n• Phone number\n• Delivery addresses\n• Password\n\n**Steps to update:**\n1. Log in to your account\n2. Go to your Profile/Account Settings\n3. Update the fields you'd like to change\n4. Click \"Save\" or \"Update\"\n\nYour updated details will be used for future orders and account communication automatically.\n\nWould you like help with anything else?",
        ],
        [
            'id' => 'delete_account',
            'group' => 'account',
            'display' => 'deleting your account',
            'keywords' => ['delete my account', 'close my account'],
            'answer' => "🗑️ **Delete Your Account**\n\nWe're sorry to see you go!\n\nIf you'd like to delete your account, please:\n1. Submit a Support Ticket\n2. Request account deletion\n3. Our team will verify your identity\n4. We'll process the request securely\n\n**Note:** This cannot be done directly in chat for security reasons.\n\nIs there anything we could do to improve your experience?",
        ],
        [
            'id' => 'email_verification',
            'group' => 'account',
            'display' => 'email verification',
            'keywords' => ['email verification', 'verify my email', 'confirmation email'],
            'answer' => "📧 **Email Verification**\n\nIf your account needs email verification:\n\n**Steps:**\n1. Check your inbox for a verification email\n2. Also check your spam/junk folder\n3. Click the verification link in the email\n4. Your account will be activated\n\n**If it never arrives:**\n• Make sure you entered the correct email\n• Check your spam folder\n• Wait a few minutes and try again\n• Contact support for assistance\n\nWould you like me to help you with anything else?",
        ],

        // --- Returns & Refunds ------------------------------------
        [
            'id' => 'return_eligibility',
            'domain' => 'return',
            'keywords' => ['return eligibility', 'am i eligible for a return', 'can i return this', 'eligible for refund'],
            'answer' => "✅ **Return Eligibility**\n\nYou're eligible for a return if:\n• Your lettuce arrived wilted or damaged\n• You received the wrong product\n• Parts were missing from your order\n• The product quality was poor upon delivery\n\n**Not eligible for return:**\n• Products that have been opened or consumed\n• Products past their freshness date\n• Products that have been improperly stored\n\n**Timeframe:**\nRequests must be made within 7 days of delivery.\n\nWould you like to start a return request?",
        ],
        [
            'id' => 'return_processing',
            'domain' => 'return',
            'keywords' => ['return processing time', 'how long does a return take', 'refund time', 'how long for a refund', 'return timeline'],
            'answer' => "⏱️ **Return Processing Time**\n\nHere's what to expect after requesting a refund:\n\n1. **Request Submitted** - Within 24 hours\n2. **Request Approved** - 1-2 business days\n3. **Refund Processed** - 3-5 business days\n4. **Refund Completed** - 5-7 business days total\n\n**Total timeline:** 5-7 business days from approval\n\nYou'll receive email updates at each stage. You can also check the status in your dashboard.\n\nWould you like to check the status of a return?",
        ],
        [
            'id' => 'replacement_info',
            'domain' => 'return',
            'keywords' => ['replacement', 'can i get a replacement', 'exchange', 'send replacement', 'new one'],
            'answer' => "🔄 **Replacement Policy**\n\nYes! For eligible issues, you can request a replacement instead of a refund:\n\n**When replacements are offered:**\n• Damaged or wilted lettuce\n• Wrong product delivered\n• Product quality issues\n\n**How it works:**\n1. Submit a Return & Refund Request\n2. Select \"Replacement\" as your preferred resolution\n3. Our team reviews and approves your request\n4. We dispatch a fresh replacement\n5. The replacement is delivered to you\n\n**Note:** All replacement requests are subject to review and approval.\n\nWould you like to request a replacement?",
        ],
        [
            'id' => 'return_window',
            'domain' => 'return',
            'keywords' => ['return window', 'how many days to return', 'return deadline', 'time limit to return'],
            'answer' => "📅 **Return Timeframe**\n\nOur return policy window:\n\n• **7 days from delivery** - You must request within 7 days\n• **Immediate reporting** - For quality issues, contact us within 24 hours\n• **Inspection period** - Our team reviews your request within 1-2 business days\n\n**Why the urgency?**\nLettuce is perishable! We need to verify the issue while the product is still fresh.\n\n**Pro Tip:**\nAlways inspect your order immediately upon delivery. If there's an issue, report it right away.\n\nWould you like to know more about our return policy?",
        ],
        [
            'id' => 'return_inspection',
            'domain' => 'return',
            'keywords' => ['return inspection', 'inspection', 'quality check', 'verify issue'],
            'answer' => "🔍 **Return Inspection Process**\n\nWhen you submit a return request:\n\n1. **Request Received** - Your request is logged\n2. **Review** - Our team reviews the details\n3. **Verification** - We may contact you for more information\n4. **Decision** - We approve or deny the request\n\n**What helps speed up the process:**\n• Clear photos of the issue\n• Detailed description\n• Order number readily available\n• Quick responses to our follow-up questions\n\n**Timeframe:** Typically 1-2 business days for review\n\nYou'll be notified of the decision by email and in your dashboard.\n\nWould you like to submit a return request?",
        ],

        // --- Wholesale information ---------------------------------
        [
            'keywords' => ['wholesale', 'bulk order', 'restaurant supply', 'commercial', 'business order', 'large order', 'partner with you', 'reseller', 'bulk purchase'],
            'answer' => "📦 **Wholesale Information**\n\nWe offer competitive wholesale pricing!\n\n**Who we work with:**\n• Restaurants - Bulk lettuce for food service\n• Grocery Stores - Retail-ready packaging\n• Hotels - Premium quality for hospitality\n• Resellers - Distribution partnerships\n• Cafes and Coffee Shops - Fresh salad ingredients\n\n**Requirements:**\n• Minimum wholesale order: ₱5,000\n• Minimum quantity: 10 units per variety\n• Delivery: Free for orders over ₱10,000\n• Payment: 50% deposit required for large orders\n\n**How to get started:**\n1. Contact our wholesale team\n2. Provide your business information\n3. Discuss your needs and requirements\n4. We'll create a custom quote\n5. Start ordering!\n\nWould you like to speak with our wholesale team directly?",
        ],

        // --- FAQ overview ------------------------------------------
        [
            'keywords' => ['frequently asked questions', 'faqs', 'faq', 'common questions', 'general question'],
            'answer' => "❓ **Frequently Asked Questions**\n\nHere are the questions we get most often:\n\n• How fresh is the lettuce?\n• What varieties do you have?\n• How do I place an order?\n• How long does delivery take?\n• What payment methods do you accept?\n• Can I cancel or modify my order?\n• How should I store my lettuce?\n• Do you offer wholesale pricing?\n• What is your return policy?\n\nFeel free to ask me any of these directly, or tell me what's on your mind!\n\nIs there something specific you'd like to know?",
        ],
        [
            'keywords' => ['how do i place an order', 'placing an order', 'how to order'],
            'answer' => "🛒 **Placing an Order**\n\nIt's easy to place an order with Fresh Lettuce Farm!\n\n**Step-by-step:**\n1. Browse our products and add items to your cart\n2. Review your cart and proceed to checkout\n3. Enter your delivery details and preferred delivery date\n4. Choose your payment method (COD, GCash, Maya, Bank Transfer)\n5. Confirm your order\n6. You'll receive a confirmation email with your order number\n\n**Tips:**\n• Create an account for faster checkout\n• Check for current promotions\n• Select express delivery for same-day delivery\n\nWould you like help with anything specific about ordering?",
        ],
        [
            'keywords' => ['payment methods', 'how can i pay', 'do you accept', 'payment options'],
            'answer' => "💳 **Payment Methods**\n\nWe accept several payment methods:\n\n• **Cash on Delivery (COD)** - Pay when you receive your order\n• **GCash** - Quick and secure mobile payment\n• **Maya** - Digital wallet payments\n• **Bank Transfer** - Direct bank transfers\n\n**Security:**\nAll payment methods are secure and encrypted. Your financial information is protected.\n\n**Payment Terms:**\n• Full payment is required at checkout\n• COD payments are due upon delivery\n• Payment is non-refundable once order is processed\n\nWould you like to know more about any specific payment method?",
        ],
        [
            'keywords' => ['do you assemble', 'assembly service', 'furniture assembly'],  // Keep but will rarely match
            'answer' => "🔧 **Assembly Service**\n\nOur lettuce products don't require assembly — they're ready to eat! 😊\n\nIf you're asking about our packaging, all our lettuce comes in:\n• Clean, food-safe packaging\n• Ready to use\n• Simple to open and enjoy\n\nWould you like to know more about our packaging?",
        ],
        [
            'keywords' => ['best seller', 'most popular', 'popular lettuce', 'customer favorite'],
            'answer' => "⭐ **Our Best Sellers**\n\nCustomers love these varieties:\n\n1. **Organic Romaine Lettuce** - ₱120\n   Crisp, versatile, and perfect for Caesar salads\n\n2. **Baby Lettuce Mix** - ₱180\n   A beautiful mix of tender young leaves\n\n3. **Premium Butterhead Lettuce** - ₱150\n   Soft, buttery texture that's perfect for wraps\n\n4. **Family Salad Pack** - ₱350\n   Great value for families who eat salads regularly\n\nWant to try one of these? I can tell you more about any of them!",
        ],

        // --- Website navigation ------------------------------------
        [
            'id' => 'website_navigation',
            'group' => 'overview',
            'keywords' => ['website navigation', 'how do i navigate this site', 'where can i find help', 'help center', 'where is the faq page'],
            'answer' => "🗺️ **Website Navigation**\n\nYou can find everything you need on our website:\n\n**Top Navigation:**\n• Home - Back to our main page\n• Products - Browse all our lettuce varieties\n• Categories - Shop by category\n• About - Learn about our farm\n• Contact - Reach out to us\n• FAQ - Find answers to common questions\n\n**Account Section:**\n• Dashboard - Manage your account\n• Orders - View your order history\n• Wishlist - Save favorite products\n• Profile - Update your information\n• Address Book - Manage delivery addresses\n\n**Quick Help:**\n• Live Chat - Chat with us right here\n• Support Tickets - Submit a support request\n• Returns/Refunds - Request a return or refund\n\nIs there a specific page you're looking for?",
        ],

        // --- Promotions --------------------------------------------
        [
            'keywords' => ['promotion', 'sale', 'discount', 'deal', 'offers', 'current promotions', 'ongoing sales'],
            'answer' => "🎉 **Current Promotions**\n\nWe currently have these great offers:\n\n• **FRESH10** - Get 10% off your first order!\n• **Free Delivery** - On orders ₱1,000+\n• **Buy 5 Get 1 Free** - Mix and match any 5 lettuce varieties!\n\n**Seasonal Specials:**\nCheck our promotions page for limited-time offers, holiday specials, and bundle deals.\n\n**Want to know more about any promotion?**\nJust let me know which one you're interested in!\n\nAlso, make sure you're subscribed to our newsletter for exclusive deals!",
        ],
        [
            'keywords' => ['newsletter', 'email list', 'subscribe', 'sign up for newsletter', 'updates'],
            'answer' => "📧 **Newsletter Subscription**\n\nStay updated with our latest news!\n\n**What you'll get:**\n• New product announcements\n• Exclusive promotions and discounts\n• Farming tips and recipes\n• Seasonal specials\n\n**How to subscribe:**\n1. Scroll to the bottom of any page\n2. Enter your email address in the newsletter signup\n3. Click \"Subscribe\"\n\n**You can unsubscribe anytime** - we'll never spam you!\n\nWould you like to know more about our promotions?",
        ],
    ],

    // =================================================================
    // 4. QUICK ACTIONS shown when the chat first opens
    // =================================================================
    'quick_actions' => [
        'primary' => [
            ['label' => '🥬 Product Info',  'message' => 'Tell me about your lettuce products'],
            ['label' => '📦 Order Help',    'message' => 'I need help with my order'],
            ['label' => '💬 Talk to Agent', 'message' => 'I want to talk to a live agent'],
        ],
        'more' => [
            ['label' => '🚚 Delivery Info',      'message' => 'Tell me about delivery and shipping'],
            ['label' => '🌱 Freshness Guide',    'message' => 'How fresh is the lettuce?'],
            ['label' => '🔄 Return & Refund',    'message' => 'I want to submit a return and refund request'],
            ['label' => '👤 Account Help',       'message' => 'I need help with my account'],
            ['label' => '📦 Wholesale Info',     'message' => 'Tell me about wholesale pricing'],
            ['label' => '❓ FAQs',               'message' => 'I have a general question'],
            ['label' => '📞 Contact Support',    'message' => 'I want to contact support'],
            ['label' => '⭐ Leave Feedback',      'message' => 'I want to leave feedback'],
            ['label' => '🤖 Back to Assistant',  'message' => 'I want to chat with the assistant again'],
        ],
    ],
];