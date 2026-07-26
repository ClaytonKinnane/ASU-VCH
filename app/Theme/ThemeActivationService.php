<?php

declare(strict_types=1);

final class ThemeActivationService
{
    public function __construct(
        private PDO $pdo,
        private ThemeRegistry $registry,
        private ThemeSettingsRepository $repository
    ) {
    }

    public function activate(string $slug, int $actorId): void
    {
        if ($actorId < 1) {
            throw new InvalidArgumentException('Некорректный субъект изменения темы.');
        }
        if (preg_match('/\A[a-z0-9][a-z0-9-]{1,63}\z/D', $slug) !== 1 || !$this->registry->isRegistered($slug) || !$this->registry->isAvailable($slug)) {
            throw new InvalidArgumentException('Выбранная тема недоступна.');
        }

        $this->pdo->beginTransaction();
        try {
            $this->repository->lockActiveTheme();
            $this->repository->saveActiveTheme($slug, $actorId, new DateTimeImmutable());
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}
