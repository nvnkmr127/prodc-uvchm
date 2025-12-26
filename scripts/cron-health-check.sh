#!/bin/bash

# Laravel College Management System - Cron Job Health Check
# Project: UVCHM Portal (/home/digiclou/portal.uvchm.com)
# Usage: ./cron-health-check.sh

echo "============================================"
echo "Laravel College Management System - Cron Health Check"
echo "Project: UVCHM Portal"
echo "Timestamp: $(date)"
echo "============================================"
echo ""

# Configuration
PROJECT_PATH="/home/digiclou/portal.uvchm.com"
LOG_PATH="/home/digiclou/logs"
CURRENT_USER=$(whoami)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to check if a file exists and show last modification
check_log_file() {
    local log_file="$1"
    local description="$2"
    local max_age_hours="$3"
    
    echo -n "  $description: "
    
    if [ -f "$log_file" ]; then
        local file_age=$(find "$log_file" -mmin -$((max_age_hours * 60)) 2>/dev/null)
        local last_modified=$(stat -c %Y "$log_file" 2>/dev/null)
        local current_time=$(date +%s)
        local age_hours=$(( (current_time - last_modified) / 3600 ))
        
        if [ ! -z "$file_age" ]; then
            echo -e "${GREEN}✓ RECENT${NC} (${age_hours}h ago)"
        else
            echo -e "${YELLOW}⚠ OLD${NC} (${age_hours}h ago)"
        fi
        echo "    File: $log_file"
        echo "    Last modified: $(date -d @$last_modified)"
        echo "    Size: $(du -h "$log_file" | cut -f1)"
    else
        echo -e "${RED}✗ MISSING${NC}"
        echo "    Expected: $log_file"
    fi
    echo ""
}

# Function to check if a process is running
check_process() {
    local process_name="$1"
    local description="$2"
    
    echo -n "  $description: "
    
    if pgrep -f "$process_name" > /dev/null; then
        echo -e "${GREEN}✓ RUNNING${NC}"
        echo "    Process count: $(pgrep -f "$process_name" | wc -l)"
        echo "    PIDs: $(pgrep -f "$process_name" | tr '\n' ' ')"
    else
        echo -e "${RED}✗ NOT RUNNING${NC}"
    fi
    echo ""
}

# Function to test Laravel artisan commands
test_artisan_command() {
    local command="$1"
    local description="$2"
    
    echo -n "  $description: "
    
    cd "$PROJECT_PATH" 2>/dev/null
    if [ $? -ne 0 ]; then
        echo -e "${RED}✗ PROJECT PATH NOT FOUND${NC}"
        return 1
    fi
    
    # Test if artisan command exists and is executable
    if timeout 10 php artisan "$command" --help >/dev/null 2>&1; then
        echo -e "${GREEN}✓ COMMAND AVAILABLE${NC}"
    else
        echo -e "${RED}✗ COMMAND FAILED${NC}"
    fi
    echo ""
}

echo "1. CHECKING CRON SERVICE STATUS"
echo "============================================"

# Check if cron service is running
echo -n "Cron Service: "
if systemctl is-active --quiet crond 2>/dev/null || systemctl is-active --quiet cron 2>/dev/null; then
    echo -e "${GREEN}✓ RUNNING${NC}"
elif service cron status >/dev/null 2>&1; then
    echo -e "${GREEN}✓ RUNNING${NC}"
else
    echo -e "${RED}✗ NOT RUNNING${NC}"
fi

# Check if crontab is configured for current user
echo -n "User Crontab ($CURRENT_USER): "
if crontab -l >/dev/null 2>&1; then
    echo -e "${GREEN}✓ CONFIGURED${NC}"
    cron_count=$(crontab -l 2>/dev/null | grep -v "^#" | grep -v "^$" | wc -l)
    echo "  Active cron jobs: $cron_count"
else
    echo -e "${RED}✗ NO CRONTAB${NC}"
fi

echo ""

echo "2. CHECKING PROJECT STRUCTURE"
echo "============================================"

# Check project directory
echo -n "Project Directory: "
if [ -d "$PROJECT_PATH" ]; then
    echo -e "${GREEN}✓ EXISTS${NC}"
    echo "  Path: $PROJECT_PATH"
    echo "  Owner: $(stat -c %U:%G "$PROJECT_PATH")"
    echo "  Permissions: $(stat -c %a "$PROJECT_PATH")"
else
    echo -e "${RED}✗ NOT FOUND${NC}"
    echo "  Expected: $PROJECT_PATH"
fi

# Check logs directory
echo -n "Logs Directory: "
if [ -d "$LOG_PATH" ]; then
    echo -e "${GREEN}✓ EXISTS${NC}"
    echo "  Path: $LOG_PATH"
    echo "  Owner: $(stat -c %U:%G "$LOG_PATH")"
    echo "  Permissions: $(stat -c %a "$LOG_PATH")"
else
    echo -e "${RED}✗ NOT FOUND${NC}"
    echo "  Expected: $LOG_PATH"
fi

# Check artisan file
echo -n "Laravel Artisan: "
if [ -f "$PROJECT_PATH/artisan" ]; then
    echo -e "${GREEN}✓ EXISTS${NC}"
    echo "  Executable: $([ -x "$PROJECT_PATH/artisan" ] && echo "Yes" || echo "No")"
else
    echo -e "${RED}✗ NOT FOUND${NC}"
fi

echo ""

echo "3. CHECKING LARAVEL ARTISAN COMMANDS"
echo "============================================"

test_artisan_command "schedule:list" "Laravel Scheduler"
test_artisan_command "queue:work --help" "Queue Worker"
test_artisan_command "optimize:clear --help" "Cache Optimization"
test_artisan_command "backup:run --help" "Backup System"
test_artisan_command "system:health-check --help" "Health Check"
test_artisan_command "payments:enhanced-reminders --help" "Payment Reminders"
test_artisan_command "settings:backup --help" "Settings Backup"

echo ""

echo "4. CHECKING RUNNING PROCESSES"
echo "============================================"

check_process "artisan queue:work" "Queue Worker Process"
check_process "artisan schedule:run" "Scheduler Process"

echo ""

echo "5. CHECKING LOG FILES (Recent Activity)"
echo "============================================"

# Check each log file with expected update frequency
check_log_file "$LOG_PATH/queue-worker.log" "Queue Worker Log" 24
check_log_file "$LOG_PATH/db-maintenance.log" "Database Maintenance Log" 48
check_log_file "$LOG_PATH/backup.log" "Backup Log" 48
check_log_file "$LOG_PATH/queue-cleanup.log" "Queue Cleanup Log" 168
check_log_file "$LOG_PATH/health-check.log" "Health Check Log" 12
check_log_file "$LOG_PATH/payment-reminders.log" "Payment Reminders Log" 48
check_log_file "$LOG_PATH/settings-backup.log" "Settings Backup Log" 48
check_log_file "$LOG_PATH/reminder-processing.log" "Reminder Processing Log" 24
check_log_file "$LOG_PATH/reminder-cleanup.log" "Reminder Cleanup Log" 168

echo ""

echo "6. CHECKING LARAVEL SPECIFIC STATUS"
echo "============================================"

if [ -d "$PROJECT_PATH" ]; then
    cd "$PROJECT_PATH"
    
    # Check Laravel scheduler
    echo -n "Laravel Scheduler Status: "
    if php artisan schedule:list >/dev/null 2>&1; then
        echo -e "${GREEN}✓ ACCESSIBLE${NC}"
        echo "  Scheduled commands:"
        php artisan schedule:list 2>/dev/null | head -10
    else
        echo -e "${RED}✗ FAILED${NC}"
    fi
    
    echo ""
    
    # Check queue status
    echo -n "Queue Configuration: "
    if php artisan queue:work --help >/dev/null 2>&1; then
        echo -e "${GREEN}✓ AVAILABLE${NC}"
    else
        echo -e "${RED}✗ FAILED${NC}"
    fi
    
    # Check failed jobs
    echo -n "Failed Jobs: "
    failed_jobs=$(php artisan queue:failed 2>/dev/null | wc -l)
    if [ $failed_jobs -gt 2 ]; then # More than header lines
        echo -e "${YELLOW}⚠ $((failed_jobs - 2)) FAILED JOBS${NC}"
    else
        echo -e "${GREEN}✓ NO FAILED JOBS${NC}"
    fi
fi

echo ""

echo "7. CHECKING RECENT CRON EXECUTION"
echo "============================================"

# Check system cron logs
echo "Recent Cron Activity (last 50 lines):"
if [ -f "/var/log/cron" ]; then
    echo "From /var/log/cron:"
    tail -50 /var/log/cron | grep "$(date +%b\ %d)" | grep "$CURRENT_USER" | tail -10
elif [ -f "/var/log/syslog" ]; then
    echo "From /var/log/syslog:"
    tail -100 /var/log/syslog | grep -i cron | grep "$(date +%b\ %d)" | tail -10
else
    echo -e "${YELLOW}⚠ System cron logs not accessible${NC}"
fi

echo ""

echo "8. MANUAL TESTING RECOMMENDATIONS"
echo "============================================"
echo "To manually test individual cron jobs, run these commands:"
echo ""
echo -e "${BLUE}# Test Laravel Scheduler:${NC}"
echo "cd $PROJECT_PATH && php artisan schedule:run"
echo ""
echo -e "${BLUE}# Test Queue Worker:${NC}"
echo "cd $PROJECT_PATH && php artisan queue:work --once"
echo ""
echo -e "${BLUE}# Test Database Maintenance:${NC}"
echo "cd $PROJECT_PATH && php artisan optimize:clear && php artisan config:cache"
echo ""
echo -e "${BLUE}# Test Backup:${NC}"
echo "cd $PROJECT_PATH && php artisan backup:run"
echo ""
echo -e "${BLUE}# Test Health Check:${NC}"
echo "cd $PROJECT_PATH && php artisan system:health-check"
echo ""
echo -e "${BLUE}# Test Payment Reminders:${NC}"
echo "cd $PROJECT_PATH && php artisan payments:enhanced-reminders"
echo ""

echo "9. TROUBLESHOOTING TIPS"
echo "============================================"
echo "If cron jobs are not working:"
echo ""
echo "1. Check cron service: sudo systemctl status cron"
echo "2. Verify crontab: crontab -l"
echo "3. Check permissions: ls -la $PROJECT_PATH/artisan"
echo "4. Test PHP CLI: which php && php -v"
echo "5. Check Laravel logs: tail -f $PROJECT_PATH/storage/logs/laravel.log"
echo "6. Monitor real-time: tail -f /var/log/cron (as root)"
echo ""

echo "============================================"
echo "Health Check Complete - $(date)"
echo "============================================"