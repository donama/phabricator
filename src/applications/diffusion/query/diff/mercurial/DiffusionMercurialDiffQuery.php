<?php

/*
 * Copyright 2011 Facebook, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

final class DiffusionMercurialDiffQuery extends DiffusionDiffQuery {

  protected function executeQuery() {
    $drequest = $this->getRequest();
    $repository = $drequest->getRepository();

    $effective_commit = $this->getEffectiveCommit();
    if (!$effective_commit) {
      return null;
    }
    // TODO: This side effect is kind of skethcy.
    $drequest->setCommit($effective_commit);

    $path = $drequest->getPath();

    list($raw_diff) = $repository->execxLocalCommand(
      'diff -U %d --git --change %s -- %s',
      65535,
      $effective_commit,
      $path);

    $parser = new ArcanistDiffParser();

    $try_encoding = $repository->getDetail('encoding');
    if ($try_encoding) {
      $parser->setTryEncoding($try_encoding);
    }

    $parser->setDetectBinaryFiles(true);
    $changes = $parser->parseDiff($raw_diff);

    $diff = DifferentialDiff::newFromRawChanges($changes);
    $changesets = $diff->getChangesets();
    $changeset = reset($changesets);

    $this->renderingReference =
      $drequest->getBranchURIComponent($drequest->getBranch()).
      $drequest->getPath().';'.
      $drequest->getCommit();

    return $changeset;
  }

}
