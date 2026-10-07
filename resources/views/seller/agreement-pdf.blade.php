<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Client Service Agreement</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.5; }
        h4 { font-size: 16px; margin-bottom: 5px; }
        h5 { font-size: 14px; margin-bottom: 5px; }
        p, ul { margin-bottom: 10px; }
        ul { padding-left: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .signature-section { margin-top: 50px; }
        .signature-line { border-top: 1px solid #000; width: 300px; margin-top: 50px; }
        .section { margin-bottom: 15px; }
        .underline { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="header">
        <h4>CLIENT SERVICE AGREEMENT</h4>
        <p><strong>SHIPXPEED LOGISTICS LLP</strong><br>
        <strong>GST No.: 07AFPFS2846L1ZA</strong></p>
    </div>

    <div class="section">
        <p>This Client Service Agreement ("Agreement") is made and entered into on this {{ $currentDate }} and between:</p>
        
        <p><strong>Shipxpeed Logistics LLP</strong>, a limited liability partnership having its registered office at E-7, 3rd Floor,
        Office No-302, Near Hira Sweets, Laxmi Nagar, Delhi - 110092, India (hereinafter referred to as "Shipxpeed" or "Company"),
        which expression shall unless it be repugnant to the context or meaning thereof be deemed to include its successors and
        permitted assigns,</p>
        
        <p>AND</p>
        
        <p><strong>Client Name: {{ $clientName }}</strong><br>
        <strong>PAN Number: {{ $clientPAN }}</strong><br>
        <strong>Address: {{ $clientAddress }}</strong><br>
        (hereinafter referred to as the "Client"), which expression shall unless it be repugnant to the context or meaning
        thereof be deemed to include its successors and permitted assigns.</p>
    </div>

    <div class="section">
        <p><strong>WHEREAS:</strong></p>
        <ul>
            <li>The Client desires to engage Shipxpeed as its logistics service provider for specific and lawful business activities;</li>
            <li>Shipxpeed agrees to provide such logistics and platform-based services under the terms and conditions set forth herein;</li>
        </ul>
    </div>

    <div class="section">
        <p><strong>NOW, THEREFORE, in consideration of the mutual covenants and promises herein contained, the parties agree as follows:</strong></p>
    </div>

    <div class="section">
        <h5>1. DEFINITIONS AND INTERPRETATION</h5>
        <ul>
            <li>"Services" means logistics, order processing, courier aggregation, returns management, and related services provided through Shipxpeed's technology platform.</li>
            <li>"COD" refers to Cash on Delivery.</li>
            <li>"RTO" refers to Return to Origin.</li>
            <li>"NDR" means Non-Delivery Report.</li>
            <li>"VAS" means Value Added Services including but not limited to influencer marketing, WhatsApp bots, and tracking support.</li>
        </ul>
    </div>

    <div class="section">
        <h5>2. SCOPE OF SERVICES</h5>
        <p>Shipxpeed shall offer the following services to the Client:</p>
        <ul>
            <li>Courier aggregation & tracking support</li>
            <li>API and panel-based order placement</li>
            <li>COD management & reconciliation</li>
            <li>Hyperlocal and national delivery</li>
            <li>NDR and RTO follow-ups</li>
            <li>Wallet-based billing for prepaid/postpaid services</li>
            <li>KYC verification and seller onboarding</li>
            <li>Value Added Services (VAS)</li>
        </ul>
        <p>Shipxpeed may update or change its services upon giving the Client a 15-day written notice.</p>
    </div>

    <div class="section">
        <h5>3. ONBOARDING AND VERIFICATION</h5>
        <ul>
            <li>The Client shall complete onboarding by submitting KYC details including PAN, GST, Aadhaar (if applicable), and bank details.</li>
            <li>All KYC will be conducted via direct API verification. No document upload is required.</li>
            <li>Upon successful verification of all mandatory fields, accounts will be auto-approved.</li>
        </ul>
    </div>

    <div class="section">
        <h5>4. PAYMENT TERMS</h5>
        <ul>
            <li>All payment is prepaid by default. No credit shall be extended unless explicitly approved by Shipxpeed.</li>
            <li>If credit-based billing is approved in writing by Shipxpeed, the Client must clear all invoices within 7 calendar days from the date of issuance.</li>
            <li>Failure to pay within 7 days will result in:
                <ul>
                    <li>An interest charge of 18% per annum on the overdue amount.</li>
                    <li>Immediate hold on all order processing and service access until payment is received in full.</li>
                </ul>
            </li>
            <li>Shipxpeed may also withhold COD remittance against any outstanding dues without further notice.</li>
        </ul>
    </div>

    <div class="section">
        <h5>5. CLIENT OBLIGATIONS</h5>
        <ul>
            <li>The Client shall not misuse the platform for any illegal or prohibited activity.</li>
            <li>All shipments must be accurately invoiced and securely packed.</li>
            <li>The Client must comply with all applicable tax and transport laws.</li>
            <li>The Client shall cooperate on failed delivery or NDR escalations.</li>
        </ul>
    </div>

    <div class="section">
        <h5>6. PROHIBITED PRODUCTS</h5>
        <p>The Client shall not use Shipxpeed for transporting or trading in:</p>
        <ul>
            <li>Narcotics or illegal drugs</li>
            <li>Alcohol, tobacco, or vape items</li>
            <li>Weapons, ammunition, explosives</li>
            <li>Currency, bullion, or gems</li>
            <li>Pornographic materials</li>
            <li>Live animals</li>
            <li>Counterfeit products</li>
            <li>Chemicals, biohazards, or hazardous items</li>
            <li>Any item restricted under Indian law</li>
        </ul>
        <p>Violation will lead to immediate suspension and legal action.</p>
    </div>

    <div class="section">
        <h5>6A. ILLEGAL ACTIVITY & LIABILITY</h5>
        <p><strong>i. Dangerous Goods:</strong></p>
        <p>The following items are classified as Dangerous Goods and are strictly prohibited from being shipped via Shipxpeed services, especially by air transport:</p>
        <ul>
            <li>Oil-based paints and thinners (flammable liquids)</li>
            <li>Industrial solvents</li>
            <li>Insecticides, garden chemicals (fertilizers, poisons)</li>
            <li>Lithium batteries</li>
            <li>Magnetized materials</li>
            <li>Machinery such as chainsaws, outboard engines containing or previously containing fuel</li>
            <li>Fuel for camp stoves, lanterns, torches, or heating elements</li>
            <li>Automobile batteries</li>
            <li>Infectious substances</li>
            <li>Any compound, liquid, or gas that possesses toxic characteristics</li>
            <li>Bleach</li>
            <li>Flammable adhesives</li>
            <li>Arms and ammunitions (including air guns)</li>
            <li>Dry ice (Carbon Dioxide, Solid)</li>
            <li>Any aerosols, liquids, and/or powders or any other flammable substances classified as Dangerous Goods for air transport</li>
        </ul>
        
        <p><strong>ii. Restricted Items:</strong></p>
        <p>The following are Restricted Items and shall not be transported via Shipxpeed services under any condition:</p>
        <ul>
            <li>Precious stones, gems, and jewellery</li>
            <li>Uncrossed (bearer) drafts, cheques, currency, and coins</li>
            <li>Poison</li>
            <li>Firearms, explosives, and military equipment</li>
            <li>Hazardous and radioactive materials</li>
            <li>Foodstuff and liquor</li>
            <li>Any form of pornographic content</li>
            <li>Hazardous chemical items</li>
        </ul>
        <p>Violation of this clause shall result in immediate account suspension and may attract legal action. Shipxpeed reserves the right to report such violations to concerned legal authorities.</p>
    </div>

    <div class="section">
        <h5>6B. DANGEROUS GOODS AND RESTRICTED ITEMS</h5>
        <ul>
            <li>If the Client is found misusing the platform for illegal trade or fraudulent activities, the Client shall bear full legal and financial liability.</li>
            <li>Shipxpeed shall not be responsible or liable for any loss, damage, penalty, or government action arising from such misuse.</li>
            <li>Any legal notice or government summons served to Shipxpeed due to the Client's actions shall be redirected to the Client, who shall be solely responsible.</li>
            <li>Shipxpeed reserves the right to immediately suspend or terminate the Client's account upon detection.</li>
        </ul>
    </div>

    <div class="section">
        <h5>7. WALLET SYSTEM</h5>
        <ul>
            <li>Client wallet will be credited or debited automatically upon order placement, cancellation, or refund.</li>
            <li>A full wallet transaction log will be available under the Client dashboard.</li>
        </ul>
    </div>

    <div class="section">
        <h5>8. PLATFORM FEATURES</h5>
        <ul>
            <li>OTP-based email verification from support@shipxpeed.com is required at account creation.</li>
            <li>Real-time rate fetching is built in, with a default 30% markup (modifiable per seller by admin).</li>
            <li>Rate card and rate calculator are dynamic and seller-specific.</li>
            <li>Shipment tracking is real-time and panel/API accessible.</li>
            <li>Ticket system is active for support resolution.</li>
            <li>KYC/bank details are downloadable in Excel format from the admin panel.</li>
        </ul>
    </div>

    <div class="section">
        <h5>9. CLAIMS AND LIABILITY</h5>
        <ul>
            <li>Claims must be submitted within 48 hours of delivery.</li>
            <li>Maximum liability for lost/damaged parcels is ₹2,500 or the invoice value, whichever is lower.</li>
            <li>No claim shall be processed without valid POD and unboxing proof.</li>
        </ul>
    </div>

    <div class="section">
        <h5>10. NON-SOLICITATION</h5>
        <ul>
            <li>The Client agrees not to solicit or contract directly with Shipxpeed's partners, staff, or vendors for 12 months post-termination.</li>
        </ul>
    </div>

    <div class="section">
        <h5>11. TERM AND TERMINATION</h5>
        <ul>
            <li>This Agreement shall remain effective unless terminated.</li>
            <li>Either party may terminate this Agreement by providing 30 days' written notice.</li>
            <li>Immediate termination applies in cases of non-payment, illegality, or breach of terms.</li>
        </ul>
    </div>

    <div class="section">
        <h5>12. INDEMNIFICATION</h5>
        <p>The Client agrees to indemnify and hold Shipxpeed harmless against any loss, claim, fine, penalty, or legal proceeding arising out of:</p>
        <ul>
            <li>Breach of this Agreement</li>
            <li>Violation of applicable laws</li>
            <li>Use of the platform for illegal or unauthorized activities</li>
        </ul>
    </div>

    <div class="section">
        <h5>13. LIMITATION OF LIABILITY</h5>
        <p>Shipxpeed shall not be liable for any indirect, incidental, or consequential losses. Total liability is limited to the service amount charged for the specific shipment.</p>
    </div>

    <div class="section">
        <h5>14. FORCE MAJEURE</h5>
        <p>Shipxpeed shall not be responsible for failure to perform due to causes beyond its control, including acts of God, internet disruptions, strikes, wars, or governmental actions.</p>
    </div>

    <div class="section">
        <h5>15. GOVERNING LAW & DISPUTES</h5>
        <p>This Agreement shall be governed by the laws of India. Any dispute shall be subject to the jurisdiction of Delhi courts. Disputes may be resolved through arbitration under the Arbitration and Conciliation Act, 1996.</p>
    </div>

    <div class="section">
        <h5>16. ENTIRE AGREEMENT</h5>
        <p>This Agreement constitutes the full agreement between the Client and Shipxpeed and supersedes all prior communications.</p>
    </div>

    <div class="section">
        <h5>17. NOTICES</h5>
        <p>All legal notices shall be sent to: Shipxpeed Logistics LLP<br>
        E-7, 3rd Floor, Office No-302, Near Hira Sweets, Laxmi Nagar, Delhi - 110092<br>
        📧support@shipxpeed.com</p>
    </div>

    <div class="section">
        <h5>18. ACCEPTANCE</h5>
        <p>I, the undersigned Client, hereby declare that I have read, understood, and agreed to all the terms mentioned in this legally binding Agreement.</p>
    </div>

    <div class="signature-section">
        <p>ACCEPTED AND AGREED:</p>
        
        <div style="float: left; width: 45%;">
            <p><strong>SHIPXPEED LOGISTICS LLP</strong></p>
            <div class="signature-line"></div>
            <p>Authorized Signatory</p>
        </div>
        
        <div style="float: right; width: 45%;">
            <p><strong>{{ strtoupper($clientName) }}</strong></p>
            <div class="signature-line"></div>
            <p>Authorized Signatory</p>
            <p>Date: {{ $currentDate }}</p>
        </div>
        
        <div style="clear: both;"></div>
    </div>
</body>
</html>