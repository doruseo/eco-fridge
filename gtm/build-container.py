"""Builds the GTM container export for the new eco-fridge.co.uk site.

Import it in GTM: Admin -> Import Container -> choose the JSON, a NEW workspace,
and "Overwrite". Every name here matches what the site pushes to the dataLayer
(GTM4WP + ecofridge-blocks/includes/analytics.php + assets/form.js).

Run: python gtm/build-container.py
"""
import json
import os
from datetime import datetime, timezone

ACC, CON = "0", "0"  # GTM replaces these with the target container on import.
MEASUREMENT_ID = "G-CTLE1GYXKG"
HOST = "www.eco-fridge.co.uk"
CONST = "{{GA4 Measurement ID}}"

ids = iter(range(1, 1000))


def base(kind_id_key):
    return {"accountId": ACC, "containerId": CON, kind_id_key: str(next(ids)), "fingerprint": "0"}


def t(key, value):
    return {"type": "TEMPLATE", "key": key, "value": value}


def b(key, value):
    return {"type": "BOOLEAN", "key": key, "value": "true" if value else "false"}


def param_table(key, rows):
    return {
        "type": "LIST",
        "key": key,
        "list": [{"type": "MAP", "map": [t("parameter", p), t("parameterValue", v)]} for p, v in rows],
    }


# ---------------------------------------------------------------- variables
variables = []


def dlv(name, path):
    v = base("variableId")
    v.update({
        "name": name,
        "type": "v",
        "parameter": [
            {"type": "INTEGER", "key": "dataLayerVersion", "value": "2"},
            b("setDefaultValue", False),
            t("name", path),
        ],
        "formatValue": {},
    })
    variables.append(v)
    return "{{" + name + "}}"


const = base("variableId")
const.update({"name": "GA4 Measurement ID", "type": "c", "parameter": [t("value", MEASUREMENT_ID)], "formatValue": {}})
variables.append(const)

dlv("DLV - ecommerce.value", "ecommerce.value")
dlv("DLV - ecommerce.transaction_id", "ecommerce.transaction_id")
dlv("DLV - ecommerce.currency", "ecommerce.currency")
dlv("DLV - ecommerce.items", "ecommerce.items")
visitor_type = dlv("DLV - visitorType", "visitorType")
page_type = dlv("DLV - pagePostType", "pagePostType")
form_params = [(p, dlv("DLV - " + p, p)) for p in ("form_id", "form_name", "form_destination", "lead_source")]

# Purchase: GA4 parameter name -> order_details field.
ORDER_PARAMS = [
    ("order_status", "order_status"),
    ("payment_method", "payment_method_title"),
    ("customer_type", "customer_type"),
    ("customer_business", "customer_business"),
    ("billing_county", "billing_county"),
    ("billing_city", "billing_city"),
    ("billing_postcode_area", "billing_postcode_area"),
    ("shipping_postcode_area", "shipping_postcode_area"),
    ("addons", "addons"),
    ("addons_total", "addons_total"),
    ("total_inc_vat", "total_inc_vat"),
    ("units_count", "units_count"),
    ("order_source", "traffic_source"),
    ("order_medium", "traffic_medium"),
    ("order_campaign", "traffic_campaign"),
    ("order_source_type", "traffic_source_type"),
    ("order_entry_page", "traffic_entry_page"),
    ("order_device", "device_type"),
    ("sessions_before_order", "session_count"),
]
purchase_params = [(ga4, dlv("DLV - od." + field, "order_details." + field)) for ga4, field in ORDER_PARAMS]

# ----------------------------------------------------------------- triggers
triggers = []


def custom_event(name, event, regex=False):
    tr = base("triggerId")
    tr.update({
        "name": name,
        "type": "CUSTOM_EVENT",
        "customEventFilter": [{
            "type": "MATCH_REGEX" if regex else "EQUALS",
            "parameter": [t("arg0", "{{_event}}"), t("arg1", event)],
        }],
        # Live site only, and never the logged-in team.
        "filter": [
            {"type": "EQUALS", "parameter": [t("arg0", "{{Page Hostname}}"), t("arg1", HOST)]},
            {"type": "MATCH_REGEX", "parameter": [t("arg0", visitor_type), t("arg1", "administrator|shop_manager"), b("negate", True)]},
        ],
    })
    triggers.append(tr)
    return tr["triggerId"]


trg_ecommerce = custom_event(
    "CE - ecommerce",
    "^(view_item_list|select_item|view_item|add_to_cart|remove_from_cart|view_cart|begin_checkout|add_shipping_info|add_payment_info)$",
    regex=True,
)
trg_purchase = custom_event("CE - purchase", "purchase")
trg_lead = custom_event("CE - generate_lead", "generate_lead")
trg_forms = custom_event("CE - form events", "^(form_start|form_submit)$", regex=True)

INITIALIZATION_ALL_PAGES = "2147479573"  # GTM built-in trigger

# --------------------------------------------------------------------- tags
tags = []
NO_EXTRA_CONSENT = {"consentStatus": "NOT_NEEDED"}  # Google tags read Consent Mode themselves.


def add_tag(name, kind, params, trigger):
    tg = base("tagId")
    tg.update({
        "name": name,
        "type": kind,
        "parameter": params,
        "firingTriggerId": [trigger],
        "tagFiringOption": "ONCE_PER_EVENT",
        "monitoringMetadata": {"type": "MAP"},
        "consentSettings": NO_EXTRA_CONSENT,
    })
    tags.append(tg)


def ga4_event(name, event_name, trigger, rows=None, ecommerce=True):
    params = [
        t("eventName", event_name),
        t("measurementIdOverride", CONST),
        b("sendEcommerceData", ecommerce),
        b("enhancedUserId", False),
    ]
    if ecommerce:
        params.append(t("getEcommerceDataFrom", "dataLayer"))
    if rows:
        params.append(param_table("eventSettingsTable", rows))
    add_tag(name, "gaawe", params, trigger)


add_tag(
    "Google tag - GA4",
    "googtag",
    [t("tagId", CONST), param_table("configSettingsTable", [("send_page_view", "true"), ("content_group", page_type)])],
    INITIALIZATION_ALL_PAGES,
)
ga4_event("GA4 Event - ecommerce", "{{Event}}", trg_ecommerce)
ga4_event("GA4 Event - purchase", "purchase", trg_purchase, purchase_params)
ga4_event("GA4 Event - generate_lead", "generate_lead", trg_lead, form_params, ecommerce=False)
ga4_event("GA4 Event - form start and submit", "{{Event}}", trg_forms, form_params, ecommerce=False)

# --------------------------------------------------------- built-in variables
built_in = [
    {"accountId": ACC, "containerId": CON, "type": kind, "name": name}
    for kind, name in (
        ("PAGE_URL", "Page URL"),
        ("PAGE_HOSTNAME", "Page Hostname"),
        ("PAGE_PATH", "Page Path"),
        ("REFERRER", "Referrer"),
        ("EVENT", "Event"),
    )
]

container = {
    "exportFormatVersion": 2,
    "exportTime": datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M:%S"),
    "containerVersion": {
        "path": f"accounts/{ACC}/containers/{CON}/versions/0",
        "accountId": ACC,
        "containerId": CON,
        "containerVersionId": "0",
        "name": "New website - GA4 ecommerce + consent",
        "container": {
            "path": f"accounts/{ACC}/containers/{CON}",
            "accountId": ACC,
            "containerId": CON,
            "name": "eco-fridge.co.uk",
            "publicId": "GTM-NFMC9Z3",
            "usageContext": ["WEB"],
            "fingerprint": "0",
            "tagManagerUrl": "",
            "tagIds": ["GTM-NFMC9Z3"],
        },
        "tag": tags,
        "trigger": triggers,
        "variable": variables,
        "builtInVariable": built_in,
        "fingerprint": "0",
        "tagManagerUrl": "",
    },
}

out = os.path.join(os.path.dirname(os.path.abspath(__file__)), "GTM-NFMC9Z3-new-website.json")
with open(out, "w", encoding="utf-8", newline="\n") as f:
    json.dump(container, f, indent=2, ensure_ascii=False)
    f.write("\n")
print(out, "|", len(tags), "tags,", len(triggers), "triggers,", len(variables), "variables,", len(built_in), "built-in")
