<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * php artisan kb:seed
 *
 * Reads every markdown (.md) file in /knowledge-base,
 * generates embeddings using Laravel's Stringable::toEmbeddings() method,
 * and stores them in the documents table with the vector column populated.
 *
 * After running this command,
 * the SupportAgent can use the SimilaritySearch tool to retrieve relevant FAQ content for a given user query.
 *
 * Run this once on setup, and again whenever we update the FAQ files.
 */
#[Signature('kb:seed {--fresh : Truncate the documents table before seeding}')]
#[Description('Embed all knowledge base markdown files into the documents table')]
class SeedKnowledgeBase extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $directory = base_path('knowledge-base');

        if (File::isDirectory($directory)) {
            $files = File::glob("{$directory}/*.md");

            if (empty($files)) {
                $this->warn('No .md files found in /knowledge-base.');
                return self::SUCCESS;
            }

            if ($this->option('fresh')) {
                Document::truncate();
                $this->line('Truncated documents table.');
            }

            $this->info('Embedding ' . count($files) . ' knowledge base files...');
            $bar = $this->output->createProgressBar(count($files));
            $bar->start();

            foreach ($files as $filepath) {
                $filename = basename($filepath);
                $content  = File::get($filepath);

                // Derive a human-readable title from the filename
                // e.g. "password-reset.md" becomes "Password Reset"
                $title = Str::of($filename)
                    ->replaceLast('.md', '')
                    ->replace('-', ' ')
                    ->title()
                    ->toString();

                // Call the configured embeddings provider and return a float array.
                // We cache embeddings to avoid re-hitting the API on repeated runs.
                $embedding = Str::of($content)->toEmbeddings(cache: true);

                // Upsert based on filename so re-running the command updates existing records rather than creating duplicates.
                Document::updateOrCreate(
                    ['filename' => $filename],
                    [
                        'title'     => $title,
                        'content'   => $content,
                        'embedding' => $embedding,
                    ]
                );

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info('Knowledge base seeded successfully.');

            return self::SUCCESS;
        } else {
            $this->error("Directory not found: {$directory}");
            $this->line('Create a /knowledge-base directory at the project root and add .md files to it.');
            return self::FAILURE;
        }
    }
}
