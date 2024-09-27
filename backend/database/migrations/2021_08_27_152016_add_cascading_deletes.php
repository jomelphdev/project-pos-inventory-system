<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCascadingDeletes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("users", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "users", ["organization_id"], "cascade");
        });

        Schema::table("user_feedback", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "user_feedback", ["organization_id", "user_id"], "cascade");
        });

        Schema::table("preferences", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "preferences", ["organization_id", "owner_id"], "cascade");
        });

        Schema::table("classifications", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "classifications", ["preference_id"], "cascade");
        });

        Schema::table("conditions", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "conditions", ["preference_id"], "cascade");
        });

        Schema::table("discounts", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "discounts", ["preference_id"], "cascade");
        });

        Schema::table("stores", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "stores", ["preference_id", "receipt_option_id", "organization_id"], "cascade");
        });

        Schema::table("receipt_options", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "receipt_options", ["preference_id"], "cascade");
        });

        Schema::table("added_items", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "added_items", ["created_by", "organization_id", "classification_id"], "cascade");
        });

        Schema::table("items", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "items", ["created_by", "organization_id", "classification_id", "condition_id"], "cascade");
        });

        Schema::table("item_images", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "item_images", ["item_id"], "cascade");
        });

        Schema::table("quantities", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "quantities", ["created_by", "store_id", "item_id"], "cascade");
        });

        Schema::table("manifests", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "manifests", ["organization_id"], "cascade");
        });

        Schema::table("manifest_items", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "manifest_items", ["organization_id", "manifest_id"], "cascade");
        });

        Schema::table("pos_orders", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "pos_orders", ["created_by", "organization_id", "store_id"], "cascade");
        });

        Schema::table("pos_order_items", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "pos_order_items", ["pos_order_id", "item_id", "added_item_id"], "cascade");
        });

        Schema::table("pos_returns", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "pos_returns", ["created_by", "organization_id", "store_id", "pos_order_id"], "cascade");
        });

        Schema::table("pos_return_items", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "pos_return_items", ["pos_return_id", "pos_order_item_id", "item_id"], "cascade");
        });

        Schema::table("reports", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "reports", ["organization_id", "store_id"], "cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("users", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "users", ["organization_id"], "no action");
        });

        Schema::table("user_feedback", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "user_feedback", ["organization_id", "user_id"], "no action");
        });

        Schema::table("preferences", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "preferences", ["organization_id", "owner_id"], "no action");
        });

        Schema::table("classifications", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "classifications", ["preference_id"], "no action");
        });

        Schema::table("conditions", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "conditions", ["preference_id"], "no action");
        });

        Schema::table("discounts", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "discounts", ["preference_id"], "no action");
        });

        Schema::table("stores", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "stores", ["preference_id", "receipt_option_id", "organization_id"], "no action");
        });

        Schema::table("receipt_options", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "receipt_options", ["preference_id"], "no action");
        });

        Schema::table("added_items", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "added_items", ["created_by", "organization_id", "classification_id"], "no action");
        });

        Schema::table("items", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "items", ["created_by", "organization_id", "classification_id", "condition_id"], "no action");
        });

        Schema::table("item_images", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "item_images", ["item_id"], "no action");
        });

        Schema::table("quantities", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "quantities", ["created_by", "store_id", "item_id"], "no action");
        });

        Schema::table("manifests", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "manifests", ["organization_id"], "no action");
        });

        Schema::table("manifest_items", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "manifest_items", ["organization_id", "manifest_id"], "no action");
        });

        Schema::table("pos_orders", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "pos_orders", ["created_by", "organization_id", "store_id"], "no action");
        });

        Schema::table("pos_order_items", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "pos_order_items", ["pos_order_id", "item_id", "added_item_id"], "no action");
        });

        Schema::table("pos_returns", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "pos_returns", ["created_by", "organization_id", "store_id", "pos_order_id"], "no action");
        });

        Schema::table("pos_return_items", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "pos_return_items", ["pos_return_id", "pos_order_item_id", "item_id"], "no action");
        });

        Schema::table("reports", function(Blueprint $table) {
            $this->dropAndAssignForeignColumns($table, "reports", ["organization_id", "store_id"], "no action");
        });
    }

    private function dropAndAssignForeignColumns(Blueprint $table, string $tablename, array $columns, string $actionOnDelete)
    {
        foreach ($columns as $column)
        {
            $constrained = null;
            $keyName = $tablename . '_' . $column . '_foreign';

            if (in_array($column, ["owner_id", "created_by"]))
            {
                $constrained = "users";
            }

            $table->dropForeign($keyName);
            $table->foreignId($column)->change()->constrained($constrained)->onDelete($actionOnDelete);
        }
    }
}
