<?php

namespace Tests\Unit;

use App\Domain\Tickets\Workflow;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class WorkflowMatrixTest extends TestCase
{
    public function test_entire_action_state_matrix_has_only_declared_edges(): void
    {
        $states = ['DRAFT', 'SUBMITTED', 'ASSIGNED', 'IN_PROGRESS', 'WAITING_STUDENT', 'RESOLVED', 'CLOSED', 'REJECTED', 'CANCELLED'];
        foreach (Workflow::TRANSITIONS as $action => [$from,$to]) {
            foreach ($states as $state) {
                if ($state === $from) {
                    $this->assertSame($to, Workflow::target($action, $state));

                    continue;
                }
                try {
                    Workflow::target($action, $state);
                    $this->fail("$action không được phép từ $state");
                } catch (HttpException $e) {
                    $this->assertSame(409, $e->getStatusCode());
                }
            }
        }
        $this->assertSame('ASSIGNED', Workflow::target('transfer', 'ASSIGNED'));
        $this->assertSame('ASSIGNED', Workflow::target('transfer', 'IN_PROGRESS'));
    }
}
