<?php

declare(strict_types=1);

namespace Ddeboer\Imap;

/**
 * An IMAP mailbox (commonly referred to as a 'folder')
 */
class Mailbox implements \Countable, \IteratorAggregate
{
    private $connection;
    private $name;
    private $info;

    /**
     * Constructor
     *
     * @param Connection $connection IMAP connection
     * @param string     $name       Mailbox decoded name
     * @param stdClass   $info       Mailbox info
     */
    public function __construct(Connection $connection, string $name, \stdClass $info)
    {
        $this->connection = $connection;
        $this->name = $name;
        $this->info = $info;
    }

    /**
     * Get mailbox decoded name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get mailbox encoded full name
     *
     * @return string
     */
    public function getFullEncodedName(): string
    {
        return $this->info->name;
    }

    /**
     * Get mailbox attributes
     *
     * @return int
     */
    public function getAttributes(): int
    {
        return $this->info->attributes;
    }

    /**
     * Get mailbox delimiter
     *
     * @return int
     */
    public function getDelimiter(): string
    {
        return $this->info->delimiter;
    }

    /**
     * Get number of messages in this mailbox
     *
     * @return int
     */
    public function count()
    {
        $this->init();

        return imap_num_msg($this->connection->getResource());
    }

    /**
     * Get message ids
     *
     * @param SearchExpression $search Search expression (optional)
     *
     * @return Message[]|MessageIterator
     */
    public function getMessages(SearchExpression $search = null): MessageIterator
    {
        $this->init();

        $query = ($search ? (string) $search : 'ALL');

        $messageNumbers = imap_search($this->connection->getResource(), $query, \SE_UID);
        if (false == $messageNumbers) {
            // imap_search can also return false
            $messageNumbers = [];
        }

        return new MessageIterator($this->connection->getResource(), $messageNumbers);
    }

    /**
     * Get a message by message number
     *
     * @param int $number Message number
     *
     * @return Message
     */
    public function getMessage(int $number): Message
    {
        $this->init();

        return new Message($this->connection->getResource(), $number);
    }

    /**
     * Get messages in this mailbox
     *
     * @return MessageIterator
     */
    public function getIterator(): MessageIterator
    {
        return $this->getMessages();
    }

    /**
     * Delete this mailbox
     */
    public function delete()
    {
        $this->connection->deleteMailbox($this);
    }

    /**
     * Delete all messages marked for deletion
     *
     * @return Mailbox
     */
    public function expunge()
    {
        $this->connection->expunge();
    }

    /**
     * Add a message to the mailbox
     *
     * @param string $message
     *
     * @return bool
     */
    public function addMessage($message): bool
    {
        return imap_append($this->connection->getResource(), $this->getFullEncodedName(), $message);
    }

    /**
     * If connection is not currently in this mailbox, switch it to this mailbox
     */
    private function init()
    {
        $check = imap_check($this->connection->getResource());
        if ($check === false || $check->Mailbox != $this->getFullEncodedName()) {
            imap_reopen($this->connection->getResource(), $this->getFullEncodedName());
        }
    }
}
