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

# Usage
show_usage() {
    echo -e "----------------------------------------"
    echo -e "${MAGENTA}Bitcoin CLI Helper${RESET}\n"
    echo -e "${MAGENTA}Usage:${RESET} $(basename "$0") ${BLUE}COMMAND${RESET} [ARGS...]\n"

    echo -e "${GREEN}Commands:${RESET}"
    echo -e "  ${GREEN}getnewaddress${RESET}                    Generate a new address"
    echo -e "  ${GREEN}generatetoaddress${RESET} ${YELLOW}BLOCKS ADDRESS${RESET} Generate blocks to address"
    echo -e "  ${GREEN}generate${RESET} ${YELLOW}NUM_BLOCKS${RESET}              Generate NUM_BLOCKS (regtest only)"
    echo -e "  ${GREEN}settxfee${RESET} ${YELLOW}FEE${RESET}                     Set transaction fee per kB"
    echo -e "  ${GREEN}sendtoaddress${RESET} ${YELLOW}ADDRESS AMOUNT${RESET}     Send funds to an address"
    echo -e "  ${GREEN}getbalance${RESET}                       Get wallet balance"
    echo -e "  ${GREEN}listtransactions${RESET}                 List recent transactions\n"

    echo -e "${CYAN}Environment Variables:${RESET}"
    echo -e "  ${CYAN}BITCOIND_CONTAINER${RESET}  Name of the container running bitcoind (default: ${CONTAINER_NAME})"
    echo -e "  ${CYAN}BITCOIND_DATA_DIR${RESET}   Data directory inside the container (default: ${DATA_DIR})"
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

# Docker Execution Wrapper
run_bitcoin_cli() {
    local output
    if ! output=$(docker compose exec -T "$CONTAINER_NAME" bitcoin-cli -datadir="$DATA_DIR" "$@" 2>&1); then
        local msg
        msg=$(echo "$output" | awk '/error message:/ {flag=1; next} flag {print}' | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')
        if [[ -n "$msg" ]]; then
            echo -e "${RED}${msg}${RESET}" >&2
        else
            echo -e "${RED}${output}${RESET}" >&2
        fi
        exit 2
    else
        if [[ "$output" == "true" ]]; then
            echo -e "${GREEN}✅ Command executed successfully${RESET}"
        else
            pretty_json "$output"
        fi
    fi
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
        run_bitcoin_cli -generate "$1"
        exit
        ;;
    generatetoaddress)
        [[ $# -lt 2 ]] && { error "Missing blocks to mine and/or address."; show_usage; exit 1; }
        ! is_positive_number "$1" && { error "Blocks must be a positive integer."; exit 1; }
        ;;
    settxfee)
        [[ $# -lt 1 ]] && { error "Missing transaction fee value."; show_usage; exit 1; }
        ! is_valid_amount "$1" && { error "Fee must be a valid number."; exit 1; }
        ;;
    sendtoaddress)
        [[ $# -lt 2 ]] && { error "Missing address and/or amount to send."; show_usage; exit 1; }
        ! is_valid_amount "$2" && { error "Amount must be a valid number."; exit 1; }
        ;;
esac

# Execute Command
run_bitcoin_cli "$COMMAND" "$@"
