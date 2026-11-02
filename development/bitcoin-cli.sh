#!/usr/bin/env bash
# Enhanced Bitcoin CLI Helper for Docker-based bitcoind (regtest by default)

set -euo pipefail

# Colors
RED="\033[1;31m"
GREEN="\033[1;32m"
YELLOW="\033[1;33m"
BLUE="\033[1;34m"
CYAN="\033[1;36m"
MAGENTA="\033[1;35m"
WHITE="\033[1;37m"
RESET="\033[0m"

error()   { echo -e "\n${RED}[ERROR]${RESET} $*\n" >&2; }

# Defaults
CONTAINER_NAME="${BITCOIND_CONTAINER:-bitcoind}"
DATA_DIR="${BITCOIND_DATA_DIR:-/data}"
WALLET_NAME="${BITCOIND_WALLET:-default}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FEE_RATE_FILE="${SCRIPT_DIR}/.bitcoin-cli-feerate"
# Regtest default when fee estimation has no data (sat/vB)
DEFAULT_FEE_RATE="${BITCOIND_FEE_RATE:-1}"

# Usage
show_usage() {
    echo -e "----------------------------------------"
    echo -e "${MAGENTA}Bitcoin CLI Helper${RESET}\n"
    echo -e "${MAGENTA}Usage:${RESET} $(basename "$0") ${BLUE}COMMAND${RESET} [ARGS...]\n"

    echo -e "${GREEN}Commands:${RESET}"
    echo -e "  ${GREEN}getnewaddress${RESET}                    Generate a new address"
    echo -e "  ${GREEN}generatetoaddress${RESET} ${YELLOW}BLOCKS ADDRESS${RESET} Generate blocks to address"
    echo -e "  ${GREEN}generate${RESET} ${YELLOW}NUM_BLOCKS${RESET}              Generate NUM_BLOCKS (regtest only)"
    echo -e "  ${GREEN}settxfee${RESET} ${YELLOW}FEE${RESET}                     Set fee (BTC/kB; native RPC or sat/vB fallback)"
    echo -e "  ${GREEN}sendtoaddress${RESET} ${YELLOW}ADDRESS AMOUNT${RESET}     Send funds to an address"
    echo -e "  ${GREEN}getbalance${RESET}                       Get wallet balance"
    echo -e "  ${GREEN}listtransactions${RESET}                 List recent transactions\n"

    echo -e "${CYAN}Environment Variables:${RESET}"
    echo -e "  ${CYAN}BITCOIND_CONTAINER${RESET}  Name of the container running bitcoind (default: ${CONTAINER_NAME})"
    echo -e "  ${CYAN}BITCOIND_DATA_DIR${RESET}   Data directory inside the container (default: ${DATA_DIR})"
    echo -e "  ${CYAN}BITCOIND_WALLET${RESET}     Wallet name to create/load (default: ${WALLET_NAME})"
    echo -e "  ${CYAN}BITCOIND_FEE_RATE${RESET}   Fallback fee rate in sat/vB when settxfee RPC is gone (default: ${DEFAULT_FEE_RATE})"
    echo -e "----------------------------------------"
}

# Command Validation
VALID_COMMANDS=("getnewaddress" "generatetoaddress" "generate" "settxfee" "sendtoaddress" "getbalance" "listtransactions")

validate_command() {
    local cmd=$1
    for valid_cmd in "${VALID_COMMANDS[@]}"; do
        [[ "$cmd" == "$valid_cmd" ]] && return 0
    done
    return 1
}

# Argument Validators
is_positive_number() {
    [[ $1 =~ ^[0-9]+$ ]]
}

is_valid_amount() {
    [[ $1 =~ ^[0-9]+(\.[0-9]+)?$ ]]
}

# JSON Pretty-Print
pretty_json() {
    local data="$1"
    if [[ "$data" =~ ^[[:space:]]*[\{\[] ]]; then
        if command -v jq >/dev/null 2>&1 && echo "$data" | jq . >/dev/null 2>&1; then
            echo "$data" | jq .
        elif command -v python3 >/dev/null 2>&1 && echo "$data" | python3 -m json.tool >/dev/null 2>&1; then
            echo "$data" | python3 -m json.tool
        elif command -v python >/dev/null 2>&1 && echo "$data" | python -m json.tool >/dev/null 2>&1; then
            echo "$data" | python -m json.tool
        else
            echo "$data"
        fi
    else
        echo "$data"
    fi
}

# Per-RPC capability cache: "name:0" / "name:1" entries (bash 3.2 has no assoc arrays).
RPC_HAS_CACHE=""

# Help text for one RPC (also used to inspect args like fee_rate / load_on_startup).
rpc_help_command() {
    local name="$1"
    docker compose exec -T "$CONTAINER_NAME" bitcoin-cli -datadir="$DATA_DIR" help "$name" 2>&1 || true
}

# True when bitcoind still exposes the named RPC (e.g. settxfee on Core < 32).
# Uses `help <cmd>` — Core prints "unknown command" when missing (often with exit 0).
rpc_has() {
    local name="$1"
    local out
    case " ${RPC_HAS_CACHE} " in
        *" ${name}:1 "*) return 0 ;;
        *" ${name}:0 "*) return 1 ;;
    esac

    out="$(rpc_help_command "$name")"
    # Missing RPCs: "unknown command". Retired RPCs (e.g. generate): "has been replaced".
    if [[ -z "$out" || "$out" == *"unknown command"* || "$out" == *"has been replaced"* ]]; then
        RPC_HAS_CACHE="${RPC_HAS_CACHE} ${name}:0"
        return 1
    fi
    RPC_HAS_CACHE="${RPC_HAS_CACHE} ${name}:1"
    return 0
}

# True when help text for an RPC mentions a given argument/flag name.
rpc_help_mentions() {
    local name="$1"
    local needle="$2"
    local out
    out="$(rpc_help_command "$name")"
    [[ "$out" != *"unknown command"* && "$out" == *"$needle"* ]]
}

# True for multiwallet-capable bitcoind (createwallet/loadwallet era).
supports_multiwallet() {
    rpc_has listwallets || rpc_has createwallet || rpc_has loadwallet
}

list_loaded_wallets() {
    if ! rpc_has listwallets; then
        echo '[]'
        return 0
    fi
    docker compose exec -T "$CONTAINER_NAME" bitcoin-cli -datadir="$DATA_DIR" listwallets 2>/dev/null || echo '[]'
}

wallet_is_loaded() {
    local wallets="$1"
    local name="$2"
    echo "$wallets" | grep -Fq "\"${name}\""
}

# Ensure a usable wallet is loaded.
# - Legacy Core: no listwallets/createwallet — single wallet always present.
# - Multiwallet: load or create BITCOIND_WALLET; accept already-loaded "" default.
ensure_wallet() {
    if ! supports_multiwallet; then
        return 0
    fi

    local wallets
    wallets="$(list_loaded_wallets)"

    if wallet_is_loaded "$wallets" "$WALLET_NAME"; then
        return 0
    fi

    # Older default wallet shows up as "" in listwallets — reuse it.
    if wallet_is_loaded "$wallets" ""; then
        WALLET_NAME=""
        return 0
    fi

    if rpc_has loadwallet; then
        if docker compose exec -T "$CONTAINER_NAME" bitcoin-cli -datadir="$DATA_DIR" \
            loadwallet "$WALLET_NAME" >/dev/null 2>&1; then
            return 0
        fi
    fi

    if ! rpc_has createwallet; then
        # Last resort: wallet RPCs may still work without an explicit load on legacy setups.
        if docker compose exec -T "$CONTAINER_NAME" bitcoin-cli -datadir="$DATA_DIR" \
            getwalletinfo >/dev/null 2>&1; then
            return 0
        fi
        error "No wallet is loaded and createwallet is unavailable on this bitcoind."
        exit 2
    fi

    local created=0
    if rpc_help_mentions createwallet load_on_startup; then
        if docker compose exec -T "$CONTAINER_NAME" bitcoin-cli -datadir="$DATA_DIR" \
            -named createwallet "wallet_name=${WALLET_NAME}" load_on_startup=true >/dev/null 2>&1; then
            created=1
        fi
    fi

    if [[ "$created" -eq 0 ]]; then
        if docker compose exec -T "$CONTAINER_NAME" bitcoin-cli -datadir="$DATA_DIR" \
            createwallet "$WALLET_NAME" >/dev/null 2>&1; then
            created=1
        fi
    fi

    if [[ "$created" -eq 0 ]]; then
        error "Failed to create wallet '${WALLET_NAME}'."
        exit 2
    fi
}

btc_per_kb_to_sat_vb() {
    # Legacy settxfee used BTC/kB; sendtoaddress fee_rate uses sat/vB.
    awk -v fee="$1" 'BEGIN {
        sat = fee * 100000
        if (sat < 1) sat = 1
        printf "%.0f", sat
    }'
}

resolve_fee_rate() {
    if [[ -f "$FEE_RATE_FILE" ]]; then
        local stored
        stored="$(tr -d '[:space:]' < "$FEE_RATE_FILE")"
        if [[ "$stored" =~ ^[0-9]+([.][0-9]+)?$ ]]; then
            echo "$stored"
            return
        fi
    fi
    echo "$DEFAULT_FEE_RATE"
}

# Prefer native settxfee on older bitcoind; emulate via fee_rate file on Core 32+.
set_tx_fee() {
    local fee="$1"
    ensure_wallet
    if rpc_has settxfee; then
        rm -f "$FEE_RATE_FILE"
        run_bitcoin_cli settxfee "$fee"
        return
    fi

    local fee_rate
    fee_rate="$(btc_per_kb_to_sat_vb "$fee")"
    printf '%s\n' "$fee_rate" > "$FEE_RATE_FILE"
    echo -e "${GREEN}Fee rate set to ${fee_rate} sat/vB${RESET} (from ${fee} BTC/kB; settxfee RPC unavailable, applied on sendtoaddress)"
}

# Use wallet paytxfee when native settxfee is available; otherwise pass fee_rate.
send_to_address() {
    local address="$1"
    local amount="$2"

    if rpc_has settxfee && [[ ! -f "$FEE_RATE_FILE" ]]; then
        run_bitcoin_cli sendtoaddress "$address" "$amount"
        return
    fi

    local fee_rate
    fee_rate="$(resolve_fee_rate)"
    if rpc_help_mentions sendtoaddress fee_rate; then
        run_bitcoin_cli -named sendtoaddress "address=${address}" "amount=${amount}" "fee_rate=${fee_rate}"
        return
    fi

    # Older sendtoaddress without fee_rate: rely on prior settxfee / node fallbackfee.
    run_bitcoin_cli sendtoaddress "$address" "$amount"
}

handle_cli_output() {
    local output="$1"
    local ok="$2"
    if [[ "$ok" != "0" ]]; then
        local msg
        msg=$(echo "$output" | awk '/error message:/ {flag=1; next} flag {print}' | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')
        if [[ -n "$msg" ]]; then
            echo -e "${RED}${msg}${RESET}" >&2
        else
            echo -e "${RED}${output}${RESET}" >&2
        fi
        exit 2
    fi

    if [[ "$output" == "true" ]]; then
        echo -e "${GREEN}✅ Command executed successfully${RESET}"
    else
        pretty_json "$output"
    fi
}

# Docker execution wrapper for wallet RPCs (-rpcwallet only when multiwallet exists).
run_bitcoin_cli() {
    local output
    local status=0

    ensure_wallet
    set +e
    if supports_multiwallet; then
        output=$(docker compose exec -T "$CONTAINER_NAME" bitcoin-cli -datadir="$DATA_DIR" \
            -rpcwallet="$WALLET_NAME" "$@" 2>&1)
    else
        output=$(docker compose exec -T "$CONTAINER_NAME" bitcoin-cli -datadir="$DATA_DIR" "$@" 2>&1)
    fi
    status=$?
    set -e
    handle_cli_output "$output" "$status"
}

# Non-wallet RPCs (e.g. generatetoaddress with an explicit address)
run_bitcoin_cli_nowallet() {
    local output
    local status=0
    set +e
    output=$(docker compose exec -T "$CONTAINER_NAME" bitcoin-cli -datadir="$DATA_DIR" "$@" 2>&1)
    status=$?
    set -e
    handle_cli_output "$output" "$status"
}

# Argument Checks
if [[ $# -lt 1 ]]; then
    error "Missing command."
    show_usage
    exit 1
fi

COMMAND=$1
shift

if ! validate_command "$COMMAND"; then
    error "Invalid command '$COMMAND'."
    show_usage
    exit 1
fi

case "$COMMAND" in
    generate)
        [[ $# -lt 1 ]] && { error "Missing number of blocks."; show_usage; exit 1; }
        ! is_positive_number "$1" && { error "Blocks must be a positive integer."; exit 1; }
        # Older Core exposes generate RPC; newer replaced it with the -generate CLI option.
        if rpc_has generate; then
            run_bitcoin_cli generate "$1"
        else
            run_bitcoin_cli -generate "$1"
        fi
        exit
        ;;
    generatetoaddress)
        [[ $# -lt 2 ]] && { error "Missing blocks to mine and/or address."; show_usage; exit 1; }
        ! is_positive_number "$1" && { error "Blocks must be a positive integer."; exit 1; }
        run_bitcoin_cli_nowallet generatetoaddress "$@"
        exit
        ;;
    settxfee)
        [[ $# -lt 1 ]] && { error "Missing transaction fee value."; show_usage; exit 1; }
        ! is_valid_amount "$1" && { error "Fee must be a valid number."; exit 1; }
        set_tx_fee "$1"
        exit
        ;;
    sendtoaddress)
        [[ $# -lt 2 ]] && { error "Missing address and/or amount to send."; show_usage; exit 1; }
        ! is_valid_amount "$2" && { error "Amount must be a valid number."; exit 1; }
        send_to_address "$1" "$2"
        exit
        ;;
    getnewaddress|getbalance|listtransactions)
        run_bitcoin_cli "$COMMAND" "$@"
        exit
        ;;
esac

run_bitcoin_cli "$COMMAND" "$@"
