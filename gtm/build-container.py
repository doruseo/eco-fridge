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

# Contact form: the only form the site's own code tracks (formId "contact" on
# /contact-us/). Its events also need this ID, so nothing else can match.
CONTACT_FORM_ID = "contact"


def cjs(name, body):
    v = base("variableId")
    v.update({"name": name, "type": "jsm", "parameter": [t("javascript", body)], "formatValue": {}})
    variables.append(v)
    return "{{" + name + "}}"


phone_number = cjs("CJS - phone number", "function() {\n  return ({{Click URL}} || '').replace(/^tel:/i, '').replace(/[^0-9+]/g, '');\n}")
email_address = cjs("CJS - email address", "function() {\n  return ({{Click URL}} || '').replace(/^mailto:/i, '').split('?')[0];\n}")
form_id_var = dict(form_params)["form_id"]

# ----------------------------------------------------------------- triggers
triggers = []

LIVE_ONLY = [
    {"type": "EQUALS", "parameter": [t("arg0", "{{Page Hostname}}"), t("arg1", HOST)]},
    {"type": "MATCH_REGEX", "parameter": [t("arg0", visitor_type), t("arg1", "administrator|shop_manager"), b("negate", True)]},
]


def custom_event(name, event, extra=None):
    tr = base("triggerId")
    tr.update({
        "name": name,
        "type": "CUSTOM_EVENT",
        "customEventFilter": [{"type": "EQUALS", "parameter": [t("arg0", "{{_event}}"), t("arg1", event)]}],
        # Live site only, and never the logged-in team.
        "filter": LIVE_ONLY + (extra or []),
    })
    triggers.append(tr)
    return tr["triggerId"]


def link_click(name, prefix):
    tr = base("triggerId")
    tr.update({
        "name": name,
        "type": "LINK_CLICK",
        "filter": LIVE_ONLY + [{"type": "STARTS_WITH", "parameter": [t("arg0", "{{Click URL}}"), t("arg1", prefix)]}],
        "waitForTags": {"type": "BOOLEAN", "value": "false"},
        "checkValidation": {"type": "BOOLEAN", "value": "false"},
        "waitForTagsTimeout": {"type": "TEMPLATE", "value": "2000"},
        "uniqueTriggerId": {"type": "TEMPLATE"},
    })
    triggers.append(tr)
    return tr["triggerId"]


ECOMMERCE_EVENTS = [
    "view_item_list", "select_item", "view_item", "add_to_cart", "remove_from_cart",
    "view_cart", "begin_checkout", "add_shipping_info", "add_payment_info",
]
ecommerce_triggers = {e: custom_event("CE - " + e, e) for e in ECOMMERCE_EVENTS}
trg_purchase = custom_event("CE - purchase", "purchase")
only_contact_form = [{"type": "EQUALS", "parameter": [t("arg0", form_id_var), t("arg1", CONTACT_FORM_ID)]}]
trg_form_start = custom_event("CE - form_start (contact form)", "form_start", only_contact_form)
trg_form_submit = custom_event("CE - form_submit (contact form)", "form_submit", only_contact_form)
trg_lead = custom_event("CE - generate_lead (contact form)", "generate_lead", only_contact_form)
trg_phone = link_click("Click - phone link", "tel:")
trg_email = link_click("Click - email link", "mailto:")

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
    "GA4 - Google tag",
    "googtag",
    [t("tagId", CONST), param_table("configSettingsTable", [("send_page_view", "true"), ("content_group", page_type)])],
    INITIALIZATION_ALL_PAGES,
)
# One tag per ecommerce event, so each one shows by name in GTM and Tag Assistant.
for e in ECOMMERCE_EVENTS:
    ga4_event("GA4 - " + e, e, ecommerce_triggers[e])
ga4_event("GA4 - purchase", "purchase", trg_purchase, purchase_params)
ga4_event("GA4 - form_start (contact form)", "form_start", trg_form_start, form_params, ecommerce=False)
ga4_event("GA4 - form_submit (contact form)", "form_submit", trg_form_submit, form_params, ecommerce=False)
ga4_event("GA4 - generate_lead (contact form)", "generate_lead", trg_lead, form_params, ecommerce=False)
ga4_event("GA4 - phone_click", "phone_click", trg_phone,
          [("phone_number", phone_number), ("link_text", "{{Click Text}}"), ("page_path", "{{Page Path}}")], ecommerce=False)
ga4_event("GA4 - email_click", "email_click", trg_email,
          [("email_address", email_address), ("link_text", "{{Click Text}}"), ("page_path", "{{Page Path}}")], ecommerce=False)

# --------------------------------------------------------- built-in variables
built_in = [
    {"accountId": ACC, "containerId": CON, "type": kind, "name": name}
    for kind, name in (
        ("PAGE_URL", "Page URL"),
        ("PAGE_HOSTNAME", "Page Hostname"),
        ("PAGE_PATH", "Page Path"),
        ("REFERRER", "Referrer"),
        ("EVENT", "Event"),
        ("CLICK_URL", "Click URL"),
        ("CLICK_TEXT", "Click Text"),
        ("CLICK_ELEMENT", "Click Element"),
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
        "name": "New website - GA4 ecommerce, contact form, phone and email clicks",
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
