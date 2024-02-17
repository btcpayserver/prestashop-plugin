#!/bin/bash
# Helper to connect to the running bitcoind (by default running regtest).

# Function to show usage instructions
show_usage() {
    cat <<EOF
Usage: bitcoin-cli-helper COMMAND [ARGS]

Commands:
  getnewaddress                    Generate a new address
  generatetoaddress BLOCKS ADDRESS Generate new blocks and send the reward to the specified address
  generate NUM_BLOCKS              Generate a specified number of blocks
  settxfee FEE                     Set the transaction fee for this network
  sendtoaddress ADDRESS AMOUNT     Send funds to an address
EOF
}

# Check if a valid command is provided
validate_command() {
    local valid_commands=("getnewaddress" "generatetoaddress" "generate" "settxfee" "sendtoaddress")
    local command=$1
    for valid_cmd in "${valid_commands[@]}"; do
        if [ "$valid_cmd" = "$command" ]; then
            return 0
        fi
    done
    return 1
}

# Validate the number of arguments
if [ "$#" -lt 1 ]; then
    echo "Error: Missing command."
    show_usage
    exit 1
fi

# Check if the provided command is valid
if ! validate_command "$1"; then
    echo "Error: Invalid command."
    show_usage
    exit 1
fi

# Execute the command with docker
if [ "$1" = "generate" ]; then
    if [ "$#" -lt 2 ]; then
        echo "Error: Missing number of blocks to generate."
        show_usage
        exit 1
    fi

    # Execute it right away, as we must pass `-`
    docker exec prestashop_bitcoind bitcoin-cli -datadir="/data" -generate "$2"
    exit 0
elif [ "$1" = "generatetoaddress" ]; then
    if [ "$#" -lt 3 ]; then
        echo "Error: Missing blocks to mine and/or address."
        show_usage
        exit 1
    fi
elif [ "$1" = "settxfee" ]; then
    if [ "$#" -lt 2 ]; then
        echo "Error: Missing transaction fee value."
        show_usage
        exit 1
    fi
elif [ "$1" = "sendtoaddress" ]; then
    if [ "$#" -lt 3 ]; then
        echo "Error: Missing address and/or amount to send."
        show_usage
        exit 1
    fi
fi

# Execute the command with docker
docker exec prestashop_bitcoind bitcoin-cli -datadir="/data" "$@"
