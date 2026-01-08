#!/bin/bash

# boot-dev.sh - Start development environment using tmux
# Runs php artisan serve (port 8000) and npm run dev (port 5173)

SESSION_NAME="kyc-dev"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Starting KYC Microservice Development Environment${NC}"
echo "=================================================="

# Function to kill process on a specific port
kill_port() {
    local port=$1
    local pid=$(lsof -ti:$port 2>/dev/null)

    if [ -n "$pid" ]; then
        echo -e "${YELLOW}Port $port is in use by PID: $pid${NC}"
        echo -e "${YELLOW}Killing process on port $port...${NC}"
        kill -9 $pid 2>/dev/null
        sleep 1
        echo -e "${GREEN}Process on port $port killed${NC}"
    else
        echo -e "${GREEN}Port $port is available${NC}"
    fi
}

# Check and kill processes on required ports
echo ""
echo "Checking required ports..."
kill_port 8000
kill_port 5173

# Check if tmux is installed
if ! command -v tmux &> /dev/null; then
    echo -e "${RED}Error: tmux is not installed${NC}"
    echo "Please install tmux: brew install tmux"
    exit 1
fi

# Kill existing session if it exists
tmux has-session -t $SESSION_NAME 2>/dev/null
if [ $? == 0 ]; then
    echo -e "${YELLOW}Existing tmux session found, killing it...${NC}"
    tmux kill-session -t $SESSION_NAME
fi

# Create new tmux session
echo ""
echo -e "${GREEN}Creating tmux session: $SESSION_NAME${NC}"

# Start tmux session with php artisan serve in the first window
tmux new-session -d -s $SESSION_NAME -n "server" "php artisan serve --host=0.0.0.0 --port=8000"

# Create second window for npm run dev
tmux new-window -t $SESSION_NAME -n "vite" "npm run dev"

# Create third window for queue worker
tmux new-window -t $SESSION_NAME -n "queue" "php artisan queue:listen --tries=1"

# Create fourth window for logs
tmux new-window -t $SESSION_NAME -n "logs" "php artisan pail --timeout=0"

# Select the first window
tmux select-window -t $SESSION_NAME:0

echo ""
echo -e "${GREEN}Development environment started!${NC}"
echo "=================================================="
echo ""
echo "Services running:"
echo "  - Laravel Server:  http://localhost:8000"
echo "  - Vite Dev Server: http://localhost:5173"
echo "  - Queue Worker:    Running"
echo "  - Log Viewer:      Running"
echo ""
echo "Tmux commands:"
echo "  - Attach to session:  tmux attach -t $SESSION_NAME"
echo "  - Switch windows:     Ctrl+b then 0-3"
echo "  - Detach:             Ctrl+b then d"
echo "  - Kill session:       tmux kill-session -t $SESSION_NAME"
echo ""

# Attach to the tmux session
tmux attach -t $SESSION_NAME