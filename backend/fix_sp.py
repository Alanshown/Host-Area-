# -*- coding: utf-8 -*-
path = r"d:\GraduationProject\backend\app\Services\ChatBotService.php"
with open(path, "r", encoding="utf-8-sig") as f:
    content = f.read()

checks = {
    "incrementMessageCount": "incrementMessageCount",
    "detectProactiveBehavior": "detectProactiveBehavior",
    "fireProactiveReply": "fireProactiveReply",
    "proactive_message": "proactive_message",
    "presenceService": "presenceService",
    "welcome_new_user": "welcome_new_user",
    "flood_warning": "flood_warning",
    "BotFaq import": "BotFaq",
    "BotNotification import": "BotNotification",
    "BotPendingAction import": "BotPendingAction",
    "faq_knowledge dispatch": "faq_knowledge",
    "notify_user dispatch": "notify_user",
    "recall_my_last_message dispatch": "recall_my_last_message",
    "pending_confirmation": "pending_confirmation",
    "bio in lookup_user": "'bio'",
}

all_ok = True
for name, pattern in checks.items():
    found = pattern in content
    status = "OK" if found else "MISSING"
    if not found:
        all_ok = False
    print(f"{status}: {name}")

print()
print("All OK!" if all_ok else "SOME MISSING!")
print(f"File size: {len(content)} chars")
