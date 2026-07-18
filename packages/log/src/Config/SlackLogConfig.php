<?php

namespace Tempest\Log\Config;

use Tempest\Log\Channels\Slack\PresentationMode;
use Tempest\Log\Channels\SlackLogChannel;
use Tempest\Log\LogChannel;
use Tempest\Log\LogConfig;
use Tempest\Log\LogLevel;
use UnitEnum;

final class SlackLogConfig implements LogConfig
{
    public array $logChannels {
        get => [
            new SlackLogChannel(
                webhookUrl: $this->webhookUrl,
                channelId: $this->channelId,
                username: $this->username,
                mode: $this->mode,
                minimumLogLevel: $this->minimumLogLevel,
            ),
            ...$this->channels,
        ];
    }

    /**
     * A logging configuration for sending log messages to a Slack channel using an Incoming Webhook.
     *
     * @param string $webhookUrl The Slack Incoming Webhook URL.
     * @param string|null $channelId The Slack channel ID to send messages to. If null, the default channel configured in the webhook will be used.
     * @param string|null $username The username to display as the sender of the message.
     * @param PresentationMode $mode The display mode for the Slack messages.
     * @param LogLevel $minimumLogLevel The minimum log level to record.
     * @param array<LogChannel> $channels Additional channels to include in the configuration.
     * @param null|string $prefix An optional prefix displayed in all log messages. By default, the current environment is used.
     * @param null|UnitEnum|string $tag An optional tag to identify the logger instance associated to this configuration.
     */
    public function __construct(
        public string $webhookUrl,
        public ?string $channelId = null,
        public ?string $username = null,
        public PresentationMode $mode = PresentationMode::INLINE,
        public LogLevel $minimumLogLevel = LogLevel::DEBUG,
        public array $channels = [],
        public ?string $prefix = null,
        public UnitEnum|string|null $tag = null,
    ) {}
}
