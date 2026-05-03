#!/bin/bash
BASE="http://127.0.0.1:8000/api/v1"
EMAIL="e2e-real-$(date +%s)@aistory.dev"
PASS="RealTest123"
FAIL=0

echo "═══════════════════════════════════════════"
echo "  REAL USER JOURNEY SIMULATION"
echo "═══════════════════════════════════════════"

# Step 1: Register
echo ""
echo "── Step 1: Register ──"
R=$(curl -s -w "\n%{http_code}" -X POST "$BASE/auth/register" -H "Content-Type: application/json" -d "{\"name\":\"RealUser\",\"email\":\"$EMAIL\",\"password\":\"$PASS\"}")
CODE=$(echo "$R" | tail -1)
BODY=$(echo "$R" | sed '$d')
TOKEN=$(echo "$BODY" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
USER_ID=$(echo "$BODY" | grep -o '"id":[0-9]*' | head -1 | cut -d: -f2)
echo "  Code: $CODE | UserID: $USER_ID | Token: ${TOKEN:0:20}..."
[ "$CODE" = "201" ] && echo "  PASS" || { echo "  FAIL: $BODY"; FAIL=$((FAIL+1)); }

# Use the token from login for consistency
T="$TOKEN"

# Step 2: Get Profile
echo "── Step 2: Get Profile ──"
R=$(curl -s -w "\n%{http_code}" "$BASE/auth/me" -H "Authorization: Bearer $T" -H "Accept: application/json")
CODE=$(echo "$R" | tail -1)
BODY=$(echo "$R" | sed '$d')
NAME=$(echo "$BODY" | grep -o '"name":"[^"]*"' | cut -d'"' -f4)
echo "  Code: $CODE | Name: $NAME"
[ "$CODE" = "200" ] && echo "  PASS" || { echo "  FAIL"; FAIL=$((FAIL+1)); }

# Step 3: Browse Models
echo "── Step 3: Browse Models ──"
R=$(curl -s -w "\n%{http_code}" "$BASE/models" -H "Accept: application/json")
CODE=$(echo "$R" | tail -1)
BODY=$(echo "$R" | sed '$d')
MODEL_COUNT=$(echo "$BODY" | grep -o '"id":[0-9]*' | wc -l)
FIRST_MODEL_ID=$(echo "$BODY" | grep -o '"id":[0-9]*' | head -1 | cut -d: -f2)
FIRST_MODEL_NAME=$(echo "$BODY" | grep -o '"display_name":"[^"]*"' | head -1 | cut -d'"' -f4)
echo "  Code: $CODE | Models: $MODEL_COUNT | First: #$FIRST_MODEL_ID '$FIRST_MODEL_NAME'"
[ "$CODE" = "200" ] && echo "  PASS" || { echo "  FAIL"; FAIL=$((FAIL+1)); }

# Step 4: View Plans
echo "── Step 4: View Plans ──"
R=$(curl -s -w "\n%{http_code}" "$BASE/plans" -H "Authorization: Bearer $T" -H "Accept: application/json")
CODE=$(echo "$R" | tail -1)
BODY=$(echo "$R" | sed '$d')
PLAN_COUNT=$(echo "$BODY" | grep -o '"tier"' | wc -l)
echo "  Code: $CODE | Plans: $PLAN_COUNT"
[ "$CODE" = "200" ] && echo "  PASS" || { echo "  FAIL"; FAIL=$((FAIL+1)); }

# Step 5: Check Membership
echo "── Step 5: Check Membership ──"
R=$(curl -s -w "\n%{http_code}" "$BASE/membership" -H "Authorization: Bearer $T" -H "Accept: application/json")
CODE=$(echo "$R" | tail -1)
BODY=$(echo "$R" | sed '$d')
CUR_PLAN=$(echo "$BODY" | grep -o '"name":"[^"]*"' | head -1 | cut -d'"' -f4)
echo "  Code: $CODE | Plan: $CUR_PLAN"
[ "$CODE" = "200" ] && echo "  PASS" || { echo "  FAIL"; FAIL=$((FAIL+1)); }

# Step 6: Configure Model Key
echo "── Step 6: Configure Model Key ──"
R=$(curl -s -w "\n%{http_code}" -X POST "$BASE/user/model-configs" -H "Authorization: Bearer $T" -H "Content-Type: application/json" -d "{\"model_registry_id\":$FIRST_MODEL_ID,\"stage\":\"script_analysis\",\"api_key\":\"sk-real-user-test-key-1234567890\"}")
CODE=$(echo "$R" | tail -1)
BODY=$(echo "$R" | sed '$d')
CFG_ID=$(echo "$BODY" | grep -o '"id":[0-9]*' | head -1 | cut -d: -f2)
MASKED=$(echo "$BODY" | grep -o '"api_key_masked":"[^"]*"' | cut -d'"' -f4)
echo "  Code: $CODE | Config ID: $CFG_ID | Masked: $MASKED"
[ "$CODE" = "201" ] && echo "  PASS" || { echo "  FAIL: $BODY"; FAIL=$((FAIL+1)); }

# Step 7: Verify Key
echo "── Step 7: Verify Key ──"
R=$(curl -s -w "\n%{http_code}" -X POST "$BASE/user/model-configs/$CFG_ID/verify" -H "Authorization: Bearer $T" -H "Content-Type: application/json")
CODE=$(echo "$R" | tail -1)
BODY=$(echo "$R" | sed '$d')
echo "  Code: $CODE | Body: $(echo $BODY | head -c 120)"
[ "$CODE" != "500" ] && echo "  PASS (non-500)" || { echo "  FAIL: 500 error"; FAIL=$((FAIL+1)); }

# Step 8: Create Work
echo "── Step 8: Create Work ──"
R=$(curl -s -w "\n%{http_code}" -X POST "$BASE/works" -H "Authorization: Bearer $T" -H "Content-Type: application/json" -d "{\"title\":\"My Journey Test Work\",\"style\":\"写实\",\"target_duration_sec\":60}")
CODE=$(echo "$R" | tail -1)
BODY=$(echo "$R" | sed '$d')
WORK_ID=$(echo "$BODY" | grep -o '"id":[0-9]*' | head -1 | cut -d: -f2)
WORK_STATUS=$(echo "$BODY" | grep -o '"status":"[^"]*"' | cut -d'"' -f4)
echo "  Code: $CODE | Work: #$WORK_ID | Status: $WORK_STATUS"
[ "$CODE" = "201" ] && echo "  PASS" || { echo "  FAIL: $BODY"; FAIL=$((FAIL+1)); }

# Step 9: View Work Detail
echo "── Step 9: View Work Detail ──"
R=$(curl -s -w "\n%{http_code}" "$BASE/works/$WORK_ID" -H "Authorization: Bearer $T" -H "Accept: application/json")
CODE=$(echo "$R" | tail -1)
BODY=$(echo "$R" | sed '$d')
WTITLE=$(echo "$BODY" | grep -o '"title":"[^"]*"' | cut -d'"' -f4)
echo "  Code: $CODE | Title: $WTITLE"
[ "$CODE" = "200" ] && echo "  PASS" || { echo "  FAIL"; FAIL=$((FAIL+1)); }

# Step 10: Update Work
echo "── Step 10: Update Work ──"
R=$(curl -s -w "\n%{http_code}" -X PUT "$BASE/works/$WORK_ID" -H "Authorization: Bearer $T" -H "Content-Type: application/json" -d "{\"title\":\"Updated Journey Work\",\"style\":\"动漫\"}")
CODE=$(echo "$R" | tail -1)
echo "  Code: $CODE"
[ "$CODE" = "200" ] && echo "  PASS" || { echo "  FAIL"; FAIL=$((FAIL+1)); }

# Step 11: Start Pipeline
echo "── Step 11: Start Pipeline ──"
R=$(curl -s -w "\n%{http_code}" -X POST "$BASE/works/$WORK_ID/pipeline/start" -H "Authorization: Bearer $T" -H "Content-Type: application/json")
CODE=$(echo "$R" | tail -1)
BODY=$(echo "$R" | sed '$d')
echo "  Code: $CODE | Response: $(echo $BODY | head -c 100)"
[ "$CODE" = "200" ] || [ "$CODE" = "202" ] || [ "$CODE" = "400" ] || [ "$CODE" = "422" ] && echo "  PASS (expected code)" || { echo "  FAIL: unexpected $CODE"; FAIL=$((FAIL+1)); }

# Step 12: Check Pipeline Progress
echo "── Step 12: Pipeline Progress ──"
R=$(curl -s -w "\n%{http_code}" "$BASE/works/$WORK_ID/pipeline/progress" -H "Authorization: Bearer $T" -H "Accept: application/json")
CODE=$(echo "$R" | tail -1)
BODY=$(echo "$R" | sed '$d')
PSTATUS=$(echo "$BODY" | grep -o '"status":"[^"]*"' | cut -d'"' -f4)
echo "  Code: $CODE | Status: $PSTATUS"
[ "$CODE" = "200" ] && echo "  PASS" || { echo "  FAIL"; FAIL=$((FAIL+1)); }

# Step 13: Delete Work
echo "── Step 13: Delete Work ──"
R=$(curl -s -w "\n%{http_code}" -X DELETE "$BASE/works/$WORK_ID" -H "Authorization: Bearer $T")
CODE=$(echo "$R" | tail -1)
echo "  Code: $CODE"
[ "$CODE" = "204" ] && echo "  PASS" || { echo "  FAIL"; FAIL=$((FAIL+1)); }

# Step 14: Delete Config
echo "── Step 14: Delete Config ──"
R=$(curl -s -w "\n%{http_code}" -X DELETE "$BASE/user/model-configs/$CFG_ID" -H "Authorization: Bearer $T")
CODE=$(echo "$R" | tail -1)
echo "  Code: $CODE"
[ "$CODE" = "204" ] && echo "  PASS" || { echo "  FAIL"; FAIL=$((FAIL+1)); }

# Step 15: Logout
echo "── Step 15: Logout ──"
R=$(curl -s -w "\n%{http_code}" -X POST "$BASE/auth/logout" -H "Authorization: Bearer $T")
CODE=$(echo "$R" | tail -1)
echo "  Code: $CODE"
[ "$CODE" = "204" ] && echo "  PASS" || { echo "  FAIL"; FAIL=$((FAIL+1)); }

# Step 16: Verify Token Revoked
echo "── Step 16: Token Revoked? ──"
R=$(curl -s -w "\n%{http_code}" "$BASE/auth/me" -H "Authorization: Bearer $T" -H "Accept: application/json")
CODE=$(echo "$R" | tail -1)
echo "  Code: $CODE"
[ "$CODE" = "401" ] && echo "  PASS" || { echo "  FAIL: token still valid!"; FAIL=$((FAIL+1)); }

echo ""
echo "═══════════════════════════════════════════"
echo "  USER JOURNEY: $FAIL failures"
echo "═══════════════════════════════════════════"
exit $FAIL
