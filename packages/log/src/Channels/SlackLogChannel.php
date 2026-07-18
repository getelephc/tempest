<?php

declare(strict_types=1);

namespace Tempest\Log\Channels;

use Monolog\Handler\SlackWebhookHandler;
use Monolog\Level;
use Monolog\Processor\PsrLogMessageProcessor;
use Tempest\Log\Channels\Slack\PresentationMode;
use Tempest\Log\LogChannel;
use Tempest\Log\LogLevel;

final readonly class SlackLogChannel implements LogChannel
{
    /**
     * @param string $webhookUrl The Slack Incoming Webhook URL.
     * @param string|null $channelId The Slack channel ID to send messages to. If null, the default channel configured in the webhook will be used.
     * @param string|null $username The username to display as the sender of the message.
     * @param PresentationMode $mode The display mode for the Slack messages.
     * @param LogLevel $minimumLogLevel The minimum log level to record.
     * @param bool $bubble Whether the messages that are handled can bubble up the stack or not.
     */
    public function __construct(
        public string $webhookUrl,
        public ?string $channelId = null,
        public ?string $username = null,
        public PresentationMode $mode = PresentationMode::INLINE,
        private LogLevel $minimumLogLevel = LogLevel::DEBUG,
        public bool $bubble = true,
    ) {}

    public function getHandlers(Level $level): array
    {
        if (! $this->minimumLogLevel->includes(LogLevel::fromMonolog($level))) {
            return [];
        }

        return [
            new SlackWebhookHandler(
                webhookUrl: $this->webhookUrl,
                channel: $this->channelId,
                username: $this->username,
                useAttachment: $this->mode === PresentationMode::BLOCKS || $this->mode === PresentationMode::BLOCKS_WITH_CONTEXT,
                includeContextAndExtra: $this->mode === PresentationMode::BLOCKS_WITH_CONTEXT,
                level: $level,
            ),
        ];
    }

    public function getProcessors(): array
    {
        return [
            new PsrLogMessageProcessor(),
        ];
    }
}
