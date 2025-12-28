# Telegram Bot Module

## Overview
This module provides Telegram bot integration for sending messages.

## Features
- Send messages via Telegram bot
- Auto-reply to incoming messages
- Configure bot token and chat ID

## Configuration
In the config view, you can:
- Set the Bot Token (from @BotFather)
- Set the Chat ID for the target chat

## Routes
- `/project/:project/telegram-bot` - Main bot view for sending messages
- `/project/:project/telegram-bot/config` - Configuration view

## Dependencies
- Telegram Bot API
