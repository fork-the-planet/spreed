<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Talk\Command\Room;

use InvalidArgumentException;
use OC\Core\Command\Base;
use OCA\Talk\Exceptions\RoomNotFoundException;
use OCA\Talk\Room;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Remove extends Base {
	use TRoomCommand;

	#[\Override]
	protected function configure(): void {
		$this
			->setName('talk:room:remove')
			->setDescription('Remove users from a room')
			->addArgument(
				'token',
				InputArgument::REQUIRED,
				'Token of the room to remove users from'
			)->addArgument(
				'participant',
				InputArgument::REQUIRED | InputArgument::IS_ARRAY,
				'Removes the given participants from the room'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$token = $input->getArgument('token');
		$users = $input->getArgument('participant');

		try {
			$room = $this->manager->getRoomByToken($token);
		} catch (RoomNotFoundException $e) {
			$output->writeln('<error>Room not found.</error>');
			return 1;
		}

		if ($room->isFederatedConversation()) {
			$output->writeln('<error>Room is a federated conversation.</error>');
			return 1;
		}

		if (!in_array($room->getType(), [Room::TYPE_GROUP, Room::TYPE_PUBLIC], true)) {
			$output->writeln('<error>Room is no group call.</error>');
			return 1;
		}

		try {
			$this->removeRoomParticipants($room, $users);
		} catch (InvalidArgumentException $e) {
			$output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
			return 1;
		}

		$output->writeln('<info>Users successfully removed from room.</info>');
		return 0;
	}

	#[\Override]
	public function completeArgumentValues($argumentName, CompletionContext $context) {
		switch ($argumentName) {
			case 'token':
				return $this->completeTokenValues($context);

			case 'participant':
				return $this->completeParticipantValues($context);
		}

		return parent::completeArgumentValues($argumentName, $context);
	}
}
